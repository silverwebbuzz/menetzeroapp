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

    /* ===================== Stripe ===================== */

    /**
     * Create a Stripe Checkout Session.
     *
     * @throws \RuntimeException on API failure
     */
    public function createStripeCheckoutSession(
        PaymentGateway $gw,
        PaymentTransaction $transaction,
        string $successUrl,
        string $cancelUrl,
        array $customer = []
    ): array {
        $response = Http::withBasicAuth($gw->key_secret, '')
            ->asForm()
            ->acceptJson()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'payment_method_types[]' => 'card',
                'client_reference_id' => (string) $transaction->id,
                'customer_email' => $customer['email'] ?? null,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => strtolower((string) $transaction->currency),
                'line_items[0][price_data][unit_amount]' => $this->toMinorUnits($transaction->amount),
                'line_items[0][price_data][product_data][name]' => $transaction->description ?: 'Subscription payment',
                'metadata[transaction_id]' => (string) $transaction->id,
                'metadata[company_id]' => (string) $transaction->company_id,
            ]);

        if ($response->failed()) {
            Log::error('Stripe checkout session creation failed', ['body' => $response->body()]);
            throw new \RuntimeException($this->extractError($response->json(), 'Could not start Stripe payment.'));
        }

        return $response->json();
    }

    /**
     * Fetch a Stripe Checkout Session by id.
     */
    public function getStripeCheckoutSession(PaymentGateway $gw, string $sessionId): ?array
    {
        $response = Http::withBasicAuth($gw->key_secret, '')
            ->acceptJson()
            ->get('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);

        if ($response->failed()) {
            Log::error('Stripe checkout session fetch failed', ['session' => $sessionId, 'body' => $response->body()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Verify Stripe webhook signature and return the decoded payload.
     */
    public function verifyStripeWebhook(string $rawPayload, string $signatureHeader, string $secret, int $tolerance = 300): ?array
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $fragment) {
            [$k, $v] = array_pad(explode('=', trim($fragment), 2), 2, null);
            if ($k && $v) {
                $parts[$k][] = $v;
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];
        if ($timestamp <= 0 || $signatures === []) {
            return null;
        }

        if (abs(time() - $timestamp) > $tolerance) {
            return null;
        }

        $signedPayload = $timestamp . '.' . $rawPayload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        $valid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            return null;
        }

        $decoded = json_decode($rawPayload, true);
        return is_array($decoded) ? $decoded : null;
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

    /**
     * Status of the latest payment attempt for a Cashfree order. Lets us tell
     * apart PENDING vs USER_DROPPED vs FAILED when the order is not yet PAID.
     *
     * @return string|null SUCCESS|FAILED|PENDING|USER_DROPPED|CANCELLED|... or null
     */
    public function getCashfreePaymentStatus(PaymentGateway $gw, string $orderId): ?string
    {
        $response = Http::withHeaders($this->cashfreeHeaders($gw))
            ->acceptJson()
            ->get($this->cashfreeBaseUrl($gw) . '/orders/' . $orderId . '/payments');

        if ($response->failed()) {
            Log::error('Cashfree payments fetch failed', ['order' => $orderId, 'body' => $response->body()]);
            return null;
        }

        $payments = $response->json();
        if (!is_array($payments) || empty($payments)) {
            return null; // No attempt recorded yet.
        }

        // The API returns attempts newest-first; the first SUCCESS wins, else
        // fall back to the most recent attempt's status.
        foreach ($payments as $payment) {
            if (($payment['payment_status'] ?? null) === 'SUCCESS') {
                return 'SUCCESS';
            }
        }

        return $payments[0]['payment_status'] ?? null;
    }

    /* ===================== Helpers ===================== */

    /**
     * Normalise a phone into the 10-digit form Cashfree expects. Returns null
     * when no usable number can be derived so callers can pick a fallback.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        // Drop a leading country code / zero so we keep the last 10 digits.
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        // Reject obviously invalid numbers (all zeros, too short).
        if (strlen($digits) < 10 || preg_match('/^0+$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Cashfree returns this when order_currency (e.g. AED) is requested but not
     * yet approved on the merchant account.
     */
    public function isCashfreeCurrencyDisabledError(string $message): bool
    {
        $msg = strtolower($message);

        return str_contains($msg, 'currency not enabled')
            || str_contains($msg, 'currency is not enabled')
            || str_contains($msg, 'order_currency');
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
