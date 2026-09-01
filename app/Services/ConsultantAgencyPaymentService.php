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

    /**
     * @param  callable(): array{charge_amount: float, charge_currency?: string}  $inrFallbackQuote
     */
    public function start(
        Company $consultantOrg,
        string $transactionType,
        string $gatewayCode,
        float $amount,
        string $currency,
        string $description,
        array $metadata,
        callable $inrFallbackQuote,
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

            // Razorpay only. Stripe and Cashfree were removed with their
            // routes -- their branches called route() on names that no longer
            // exist, which would have thrown after the transaction row was
            // already written.
            //
            // The AED -> INR fallback is kept, now on Razorpay: a standard
            // Indian account accepts INR only, and AED needs International
            // Payments activated. $inrFallbackQuote() re-prices rather than
            // converting, so the INR amount is the real INR list price.
            try {
                $rzOrder = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $transaction->amount,
                    $transaction->currency,
                    'consultant_' . $transaction->id,
                    ['type' => $transactionType, 'consultant_id' => (string) $consultantOrg->id]
                );
            } catch (\RuntimeException $e) {
                if ($transaction->currency === 'INR'
                    || !$this->paymentService->isRazorpayCurrencyDisabledError($e->getMessage())) {
                    throw $e;
                }

                $inrQuote = $inrFallbackQuote();

                // Preserve the price that was quoted BEFORE overwriting amount
                // and currency. The invoice is denominated in the currency the
                // customer agreed to; without this the AED figure is lost and
                // only the INR settlement survives.
                $meta['quoted_amount'] = (float) $transaction->amount;
                $meta['quoted_currency'] = strtoupper((string) $transaction->currency);

                $transaction->update([
                    'amount' => $inrQuote['charge_amount'],
                    'currency' => 'INR',
                ]);
                $meta['charged_in_inr_fallback'] = true;

                // metadata is persisted at the end of the try block, but an
                // exception between here and there would lose the quote, so
                // write it now -- the invoice depends on it.
                $transaction->metadata = $meta;
                $transaction->save();

                $rzOrder = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $inrQuote['charge_amount'],
                    'INR',
                    'consultant_' . $transaction->id,
                    ['type' => $transactionType, 'consultant_id' => (string) $consultantOrg->id]
                );

                session()->flash('info', 'Charged in the INR equivalent while AED activation is pending with our payment provider.');
            }

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
