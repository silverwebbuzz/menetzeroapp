<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Razorpay REST API.
 *
 * Uses the Laravel HTTP client directly (no vendor SDK) so it works without an
 * extra composer dependency. Credentials come from the admin-managed
 * `payment_gateways` table.
 */
class PaymentService
{
    /** Convert a major-unit amount (e.g. 3650.00) to minor units (paise/fils). */
    public function toMinorUnits($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /* ===================== Razorpay ===================== */

    /**
     * Create a Razorpay order. Returns the decoded order (contains `id`).
     *
     * @throws \RuntimeException on API failure
     */
    public function createRazorpayOrder(PaymentGateway $gw, $amount, string $currency, string $receipt, array $notes = []): array
    {
        $response = Http::withBasicAuth($gw->key_id, $gw->key_secret)
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $this->toMinorUnits($amount),
                'currency' => strtoupper($currency),
                'receipt' => $receipt,
                'notes' => $notes,
                'payment_capture' => 1,
            ]);

        if ($response->failed()) {
            Log::error('Razorpay order creation failed', ['body' => $response->body()]);
            throw new \RuntimeException($this->extractError($response->json(), 'Could not start Razorpay payment.'));
        }

        return $response->json();
    }

    /**
     * Verify the signature returned by Razorpay Checkout.
     */
    public function verifyRazorpaySignature(PaymentGateway $gw, string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, (string) $gw->key_secret);

        return hash_equals($expected, $signature);
    }

    /* ===================== Helpers ===================== */

    /**
     * Razorpay rejects an order when the requested currency is not enabled on
     * the merchant account. A standard Indian Razorpay account accepts INR
     * only; AED needs International Payments activated.
     *
     * Matched on the message because Razorpay returns a generic
     * BAD_REQUEST_ERROR for this, with the detail only in the description.
     * Deliberately narrow -- a false positive here would silently charge a
     * customer in the wrong currency, so anything unrecognised is rethrown.
     */
    public function isRazorpayCurrencyDisabledError(string $message): bool
    {
        $msg = strtolower($message);

        return str_contains($msg, 'currency is not supported')
            || str_contains($msg, 'currency not supported')
            || str_contains($msg, 'international payments')
            || str_contains($msg, 'not enabled for international')
            || (str_contains($msg, 'currency') && str_contains($msg, 'not allowed'));
    }

    private function extractError(?array $body, string $fallback): string
    {
        if (is_array($body)) {
            return $body['error']['description']
                ?? $body['message']
                ?? $fallback;
        }

        return $fallback;
    }
}
