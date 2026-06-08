<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Razorpay and Cashfree REST APIs.
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

    /* ===================== Cashfree ===================== */

    private function cashfreeBaseUrl(PaymentGateway $gw): string
    {
        return $gw->isLive()
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    private function cashfreeHeaders(PaymentGateway $gw): array
    {
        return [
            'x-client-id' => $gw->key_id,
            'x-client-secret' => $gw->key_secret,
            'x-api-version' => '2023-08-01',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Create a Cashfree order. Returns the decoded order (contains
     * `payment_session_id` and `order_id`).
     *
     * @throws \RuntimeException on API failure
     */
    public function createCashfreeOrder(PaymentGateway $gw, string $orderId, $amount, string $currency, array $customer, string $returnUrl): array
    {
        $response = Http::withHeaders($this->cashfreeHeaders($gw))
            ->acceptJson()
            ->post($this->cashfreeBaseUrl($gw) . '/orders', [
                'order_id' => $orderId,
                'order_amount' => (float) $amount,
                'order_currency' => strtoupper($currency),
                'customer_details' => [
                    'customer_id' => (string) ($customer['id'] ?? 'guest'),
                    'customer_name' => $customer['name'] ?? '',
                    'customer_email' => $customer['email'] ?? '',
                    'customer_phone' => $customer['phone'] ?? '0000000000',
                ],
                'order_meta' => [
                    'return_url' => $returnUrl,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Cashfree order creation failed', ['body' => $response->body()]);
            throw new \RuntimeException($this->extractError($response->json(), 'Could not start Cashfree payment.'));
        }

        return $response->json();
    }

    /**
     * Fetch the current order status from Cashfree (e.g. PAID, ACTIVE, EXPIRED).
     */
    public function getCashfreeOrderStatus(PaymentGateway $gw, string $orderId): ?string
    {
        $response = Http::withHeaders($this->cashfreeHeaders($gw))
            ->acceptJson()
            ->get($this->cashfreeBaseUrl($gw) . '/orders/' . $orderId);

        if ($response->failed()) {
            Log::error('Cashfree order fetch failed', ['order' => $orderId, 'body' => $response->body()]);
            return null;
        }

        return $response->json('order_status');
    }

    /* ===================== Helpers ===================== */

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
