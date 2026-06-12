<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Server-to-server payment notifications from Razorpay and Cashfree.
 *
 * These run outside the auth/CSRF middleware (see bootstrap/app.php). Every
 * request is authenticated by verifying the gateway signature against the
 * stored webhook secret. Activation is idempotent.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Razorpay webhook. Verifies HMAC-SHA256 of the raw body.
     */
    public function razorpay(Request $request)
    {
        $gateway = PaymentGateway::forGateway('razorpay');
        $secret = $gateway?->webhook_secret;

        if (!$secret) {
            return response()->json(['message' => 'Webhook not configured'], 400);
        }

        $raw = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature');
        $expected = hash_hmac('sha256', $raw, $secret);

        if (!hash_equals($expected, $signature)) {
            Log::warning('Razorpay webhook signature mismatch');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;

        // We only act on successful payment/order events.
        $orderId = $payload['payload']['payment']['entity']['order_id']
            ?? $payload['payload']['order']['entity']['id']
            ?? null;
        $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;

        if (in_array($event, ['payment.captured', 'order.paid'], true) && $orderId) {
            $transaction = PaymentTransaction::where('metadata->razorpay_order_id', $orderId)->first();
            if ($transaction) {
                $this->activate($transaction, array_filter([
                    'razorpay_order_id' => $orderId,
                    'razorpay_payment_id' => $paymentId,
                    'source' => 'webhook',
                ]));
            }
        }

        // Always 200 on a verified request so the gateway stops retrying.
        return response()->json(['status' => 'ok']);
    }

    /**
     * Cashfree webhook. Verifies base64(HMAC-SHA256(timestamp + body)).
     */
    public function cashfree(Request $request)
    {
        $gateway = PaymentGateway::forGateway('cashfree');
        // Cashfree signs with the secret key; allow a dedicated webhook secret too.
        $secret = $gateway?->webhook_secret ?: $gateway?->key_secret;

        if (!$gateway || !$secret) {
            return response()->json(['message' => 'Webhook not configured'], 400);
        }

        $raw = $request->getContent();
        $timestamp = (string) $request->header('x-webhook-timestamp');
        $signature = (string) $request->header('x-webhook-signature');
        $expected = base64_encode(hash_hmac('sha256', $timestamp . $raw, $secret, true));

        if (!hash_equals($expected, $signature)) {
            Log::warning('Cashfree webhook signature mismatch');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $payload = $request->json()->all();
        $orderId = $payload['data']['order']['order_id'] ?? null;
        $type = $payload['type'] ?? null;
        $paymentStatus = $payload['data']['payment']['payment_status'] ?? null;

        if ($orderId) {
            $transaction = PaymentTransaction::where('metadata->cashfree_order_id', $orderId)->first();

            if ($transaction && $transaction->status !== 'completed') {
                $success = $type === 'PAYMENT_SUCCESS_WEBHOOK' || $paymentStatus === 'SUCCESS';

                if ($success) {
                    // Confirm with the orders API before activating.
                    if ($this->paymentService->getCashfreeOrderStatus($gateway, $orderId) === 'PAID') {
                        $this->activate($transaction, ['cashfree_order_id' => $orderId, 'source' => 'webhook']);
                    }
                } elseif (in_array($paymentStatus, ['USER_DROPPED', 'CANCELLED'], true)) {
                    $transaction->update(['status' => 'cancelled']);
                } elseif ($paymentStatus === 'FAILED' || $type === 'PAYMENT_FAILED_WEBHOOK') {
                    $transaction->update(['status' => 'failed']);
                }
                // PENDING and other transient states: leave as-is and wait.
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function activate(PaymentTransaction $transaction, array $refs): void
    {
        try {
            app(\App\Services\PaymentCompletionService::class)->complete($transaction, $refs);
        } catch (\Throwable $e) {
            Log::error('Webhook payment completion failed', [
                'transaction_id' => $transaction->id,
                'refs' => $refs,
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            try {
                app(\App\Services\EmailTemplateService::class)->sendSystemAlert(
                    'Webhook payment completion failed',
                    'A paid webhook could not activate the subscription or order.',
                    [
                        'transaction_id' => $transaction->id,
                        'refs' => $refs,
                        'error' => $e->getMessage(),
                    ]
                );
            } catch (\Throwable $mailError) {
                Log::error('Failed to send system alert email', ['error' => $mailError->getMessage()]);
            }
        }
    }
}
