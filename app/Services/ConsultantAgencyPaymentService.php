<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ConsultantAgencyPaymentService
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {
    }

    public function start(
        Company $consultantOrg,
        string $transactionType,
        string $gatewayCode,
        float $amount,
        string $currency,
        string $description,
        array $metadata,
    ): RedirectResponse {
        $gateway = PaymentGateway::forGateway($gatewayCode);

        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return back()->with('error', 'The selected payment method is not available.');
        }

        $displayCurrency = CurrencyService::displayCurrency();

        $transaction = PaymentTransaction::create([
            'company_id' => $consultantOrg->id,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'transaction_type' => $transactionType,
                'display_currency' => $displayCurrency,
            ]),
        ]);

        try {
            $user = Auth::user();
            $meta = $transaction->metadata;

            // Razorpay only, AED only. Stripe and Cashfree were removed with
            // their routes.
            //
            // The AED -> INR fallback that used to live here is gone on
            // purpose: the seller is an Indian entity exporting services to
            // UAE buyers, and charging INR would recharacterise a zero-rated
            // export as a domestic supply. AED needs International Payments
            // active on the Razorpay account; if it is not, this throws and
            // the sale fails visibly, which is recoverable. A silent INR
            // charge is not.
            $rzOrder = $this->paymentService->createRazorpayOrder(
                $gateway,
                $transaction->amount,
                $transaction->currency,
                'consultant_' . $transaction->id,
                ['type' => $transactionType, 'consultant_id' => (string) $consultantOrg->id]
            );

            $meta['razorpay_order_id'] = $rzOrder['id'] ?? null;

            $transaction->metadata = $meta;
            $transaction->save();

            return redirect()->route('consultant.packs.payment.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }
}
