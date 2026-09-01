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
            // Issue BEFORE the emails: sendPaymentNotifications() looks the
            // invoice up to fill invoice_number / invoice_url and to attach the
            // PDF. Issuing is idempotent per transaction, so a webhook arriving
            // after the callback reuses the same document.
            try {
                app(InvoiceService::class)->issueFor($fresh);
            } catch (\Throwable $e) {
                \Log::error('Invoice generation failed', [
                    'transaction_id' => $fresh->id,
                    'error' => $e->getMessage(),
                ]);
            }

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
