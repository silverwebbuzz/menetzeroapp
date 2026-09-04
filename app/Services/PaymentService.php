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

    /**
     * Fetch a payment from Razorpay by its id.
     *
     * verifyRazorpaySignature() only proves the values the BROWSER handed back
     * were not tampered with; it says nothing about whether money moved, and it
     * cannot be used at all when there is no browser callback -- which is
     * exactly the situation an admin repairing a stuck transaction is in.
     * This asks Razorpay directly.
     *
     * @return array{id: string, status: string, amount: int, currency: string, order_id: ?string}|null
     *         null when the payment is unknown to Razorpay.
     * @throws \RuntimeException when the API cannot be reached
     */
    public function fetchRazorpayPayment(PaymentGateway $gw, string $paymentId): ?array
    {
        $response = Http::withBasicAuth($gw->key_id, $gw->key_secret)
            ->acceptJson()
            ->timeout(15)
            ->get('https://api.razorpay.com/v1/payments/' . urlencode($paymentId));

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            Log::error('Razorpay payment fetch failed', [
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($this->extractError($response->json(), 'Could not reach Razorpay to verify this payment.'));
        }

        return $response->json();
    }

    /**
     * Does a fetched Razorpay payment settle the given transaction?
     *
     * Guards the admin repair action: it must be impossible to activate a
     * subscription for money that never arrived, whoever clicks the button.
     * `captured` is the only status that means the funds are ours --
     * `authorized` is a hold that can still expire, and Razorpay reports
     * amounts in minor units, so both sides are compared in fils.
     *
     * @param  array<string, mixed>  $payment
     * @return array{ok: bool, reason: ?string}
     */
    public function razorpayPaymentSettles(array $payment, \App\Models\PaymentTransaction $transaction): array
    {
        $status = (string) ($payment['status'] ?? '');

        if ($status !== 'captured') {
            return [
                'ok' => false,
                'reason' => "Razorpay reports this payment as '{$status}', not 'captured'. No funds have been settled.",
            ];
        }

        $paidMinor = (int) ($payment['amount'] ?? 0);
        $dueMinor = $this->toMinorUnits($transaction->amount);

        if ($paidMinor !== $dueMinor) {
            return [
                'ok' => false,
                'reason' => sprintf(
                    'Amount mismatch: Razorpay captured %s %s, the transaction is for %s %s.',
                    number_format($paidMinor / 100, 2),
                    strtoupper((string) ($payment['currency'] ?? '?')),
                    number_format((float) $transaction->amount, 2),
                    strtoupper((string) $transaction->currency)
                ),
            ];
        }

        if (strtoupper((string) ($payment['currency'] ?? '')) !== strtoupper((string) $transaction->currency)) {
            return [
                'ok' => false,
                'reason' => 'Currency mismatch between the Razorpay payment and this transaction.',
            ];
        }

        // The order id ties the payment to THIS transaction rather than another
        // of the same value. Only checked when the transaction recorded one.
        $expectedOrder = $transaction->metadata['razorpay_order_id'] ?? null;
        $actualOrder = $payment['order_id'] ?? null;

        if ($expectedOrder && $actualOrder && $expectedOrder !== $actualOrder) {
            return [
                'ok' => false,
                'reason' => 'This payment belongs to a different Razorpay order than the one on this transaction.',
            ];
        }

        return ['ok' => true, 'reason' => null];
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
