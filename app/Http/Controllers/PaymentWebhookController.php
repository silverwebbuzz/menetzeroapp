<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Server-to-server payment notifications from Razorpay.
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

        // is_enabled is checked as well as the secret. Disabling a gateway in
        // admin must actually close its payment path: a disabled row keeps its
        // credentials so historical transactions still resolve by name, so the
        // presence of a secret is not evidence the gateway is in service.
        if (!$gateway?->is_enabled || !$secret) {
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

        // A declined card is the only signal we get that an attempt ended:
        // the payer never returns through razorpayCallback(), so without this
        // the row sits at 'pending' forever while Razorpay emails the merchant
        // that it failed. Recording it keeps the billing history honest and
        // stops abandoned attempts from being mistaken for pending ones.
        if ($event === 'payment.failed' && $orderId) {
            $this->recordFailure($orderId, $paymentId, $payload);
        }

        // Always 200 on a verified request so the gateway stops retrying.
        return response()->json(['status' => 'ok']);
    }

    /**
     * Mark a transaction failed after a declined payment.
     *
     * Deliberately narrow: only a row still awaiting its outcome is touched.
     * A payment can fail and then succeed on retry against the same order, and
     * webhooks can arrive out of order, so a transaction that already reached
     * 'completed' must never be walked back to 'failed' by a late notification.
     */
    private function recordFailure(string $orderId, ?string $paymentId, array $payload): void
    {
        $transaction = PaymentTransaction::where('metadata->razorpay_order_id', $orderId)->first();

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        $entity = $payload['payload']['payment']['entity'] ?? [];

        $metadata = $transaction->metadata ?? [];
        $metadata['failure'] = array_filter([
            'razorpay_payment_id' => $paymentId,
            'code' => $entity['error_code'] ?? null,
            'description' => $entity['error_description'] ?? null,
            'reason' => $entity['error_reason'] ?? null,
            'source' => 'webhook',
            'at' => now()->toIso8601String(),
        ]);

        $transaction->status = 'failed';
        $transaction->metadata = $metadata;
        $transaction->save();

        Log::info('Razorpay payment failed', [
            'transaction_id' => $transaction->id,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'code' => $entity['error_code'] ?? null,
        ]);
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
