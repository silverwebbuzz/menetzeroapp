<?php

namespace App\Services;

use App\Models\ClientSubscription;
use App\Models\ConsultantOrder;
use App\Models\ConsultantSubscription;
use App\Models\ConsultantSubscriptionAddon;
use App\Models\PaymentTransaction;

class PaymentCompletionService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected ConsultantMarketplaceService $marketplace,
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
        protected ConsultantAgencyRenewalService $consultantRenewals,
    ) {}

    public function complete(
        PaymentTransaction $transaction,
        array $gatewayRefs = [],
    ): ClientSubscription|ConsultantOrder|ConsultantSubscription|ConsultantSubscriptionAddon {
        $type = $transaction->metadata['transaction_type']
            ?? $transaction->transaction_type
            ?? 'subscription';

        $result = match ($type) {
            'consultant_pack' => $this->marketplace->completeTransaction($transaction, $gatewayRefs),
            'consultant_agency_pack' => $this->consultantSubscriptions->completePackTransaction($transaction, $gatewayRefs),
            'consultant_agency_extra_slot' => $this->consultantSubscriptions->completeExtraSlotTransaction($transaction, $gatewayRefs),
            'consultant_agency_year_unlock' => $this->consultantSubscriptions->completeYearUnlockTransaction($transaction, $gatewayRefs),
            'consultant_agency_renewal' => $this->consultantRenewals->completeRenewalTransaction($transaction, $gatewayRefs),
            default => $this->subscriptions->completeTransaction($transaction, $gatewayRefs),
        };

        $fresh = $transaction->fresh();
        if ($fresh && $fresh->status === 'completed') {
            try {
                app(EmailTemplateService::class)->sendPaymentNotifications($fresh);
            } catch (\Throwable $e) {
                \Log::error('Payment notification email failed', [
                    'transaction_id' => $fresh->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }
}
