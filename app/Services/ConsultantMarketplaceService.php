<?php

namespace App\Services;

use App\Data\CommercialPlanComparison;
use App\Models\Consultant;
use App\Models\ConsultantOrder;
use App\Models\PaymentTransaction;
use App\Models\SiteSetting;

class ConsultantMarketplaceService
{
    public function chargeAmount(float $amountAed, string $gateway): array
    {
        if ($gateway === 'cashfree') {
            return [
                'currency' => 'AED',
                'amount' => $amountAed,
            ];
        }

        $rate = (float) SiteSetting::get('aed_inr_rate', 22.5);

        return [
            'currency' => 'INR',
            'amount' => round($amountAed * $rate, 0),
        ];
    }

    public function commissionRate(): float
    {
        $rate = (float) SiteSetting::get('consultant_commission_rate', 0.15);

        return max(0, min(1, $rate));
    }

    public function packAmountAed(string $packType): float
    {
        $pack = CommercialPlanComparison::consultantPackByType($packType);

        return (float) ($pack['price_aed'] ?? 0);
    }

    public function calculateSplit(float $amountAed, ?float $rate = null): array
    {
        $rate ??= $this->commissionRate();
        $commission = round($amountAed * $rate, 2);
        $payout = round($amountAed - $commission, 2);

        return [
            'commission_rate' => $rate,
            'commission_aed' => $commission,
            'payout_aed' => $payout,
        ];
    }

    public function createPendingOrder(
        int $companyId,
        Consultant $consultant,
        string $packType,
        ?int $introRequestId = null,
    ): ConsultantOrder {
        $amount = $this->packAmountAed($packType);
        $split = $this->calculateSplit($amount);

        return ConsultantOrder::create([
            'company_id' => $companyId,
            'consultant_id' => $consultant->id,
            'intro_request_id' => $introRequestId,
            'pack_type' => $packType,
            'amount_aed' => $amount,
            'commission_rate' => $split['commission_rate'],
            'commission_aed' => $split['commission_aed'],
            'payout_aed' => $split['payout_aed'],
            'escrow_status' => 'pending_payment',
            'order_status' => 'draft',
        ]);
    }

    /**
     * Activate escrow after successful payment. Idempotent.
     */
    public function completeTransaction(PaymentTransaction $transaction, array $gatewayRefs = []): ConsultantOrder
    {
        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $orderId = $metadata['consultant_order_id'] ?? null;

        if (!$orderId) {
            throw new \RuntimeException('Transaction is missing consultant_order_id.');
        }

        $order = ConsultantOrder::findOrFail($orderId);

        if ($transaction->status === 'completed') {
            return $order;
        }

        $order->update([
            'payment_transaction_id' => $transaction->id,
            'payment_reference' => $metadata['razorpay_payment_id']
                ?? $metadata['cashfree_order_id']
                ?? $transaction->stripe_payment_intent_id,
            'escrow_status' => 'held',
            'order_status' => 'active',
        ]);

        $transaction->update([
            'status' => 'completed',
            'paid_at' => now(),
            'metadata' => $metadata,
            'description' => $transaction->description ?: 'Consultant review pack',
        ]);

        return $order->fresh();
    }

    public function markDelivered(ConsultantOrder $order): ConsultantOrder
    {
        $order->update([
            'order_status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return $order->fresh();
    }

    public function releaseEscrow(ConsultantOrder $order): ConsultantOrder
    {
        if ($order->escrow_status !== 'held') {
            throw new \RuntimeException('Escrow is not held for this order.');
        }

        $order->update([
            'escrow_status' => 'released',
            'order_status' => 'completed',
            'completed_at' => now(),
        ]);

        return $order->fresh();
    }

    public function refundEscrow(ConsultantOrder $order): ConsultantOrder
    {
        if (!in_array($order->escrow_status, ['held', 'pending_payment'], true)) {
            throw new \RuntimeException('Order cannot be refunded in current escrow state.');
        }

        $order->update([
            'escrow_status' => 'refunded',
            'order_status' => 'cancelled',
        ]);

        return $order->fresh();
    }
}
