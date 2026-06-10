<?php

namespace App\Services;

use App\Models\ClientSubscription;
use App\Models\ConsultantOrder;
use App\Models\PartnerSubscription;
use App\Models\PartnerSubscriptionAddon;
use App\Models\PaymentTransaction;

class PaymentCompletionService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected ConsultantMarketplaceService $marketplace,
        protected PartnerSubscriptionService $partnerSubscriptions,
        protected PartnerRenewalService $partnerRenewals,
    ) {}

    public function complete(
        PaymentTransaction $transaction,
        array $gatewayRefs = [],
    ): ClientSubscription|ConsultantOrder|PartnerSubscription|PartnerSubscriptionAddon {
        $type = $transaction->metadata['transaction_type']
            ?? $transaction->transaction_type
            ?? 'subscription';

        return match ($type) {
            'consultant_pack' => $this->marketplace->completeTransaction($transaction, $gatewayRefs),
            'partner_pack' => $this->partnerSubscriptions->completePackTransaction($transaction, $gatewayRefs),
            'partner_extra_slot' => $this->partnerSubscriptions->completeExtraSlotTransaction($transaction, $gatewayRefs),
            'partner_year_unlock' => $this->partnerSubscriptions->completeYearUnlockTransaction($transaction, $gatewayRefs),
            'partner_renewal' => $this->partnerRenewals->completeRenewalTransaction($transaction, $gatewayRefs),
            default => $this->subscriptions->completeTransaction($transaction, $gatewayRefs),
        };
    }
}
