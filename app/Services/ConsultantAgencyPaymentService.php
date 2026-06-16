<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

            if ($gateway->gateway === 'razorpay') {
                $rzOrder = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $transaction->amount,
                    $transaction->currency,
                    'consultant_' . $transaction->id,
                    ['type' => $transactionType, 'consultant_id' => (string) $consultantOrg->id]
                );
                $meta['razorpay_order_id'] = $rzOrder['id'] ?? null;
            } elseif ($gateway->gateway === 'stripe') {
                $session = $this->paymentService->createStripeCheckoutSession(
                    $gateway,
                    $transaction,
                    route('consultant.packs.payment.stripe') . '?session_id={CHECKOUT_SESSION_ID}&transaction_id=' . $transaction->id,
                    route('consultant.packs.payment.checkout', $transaction->id),
                    [
                        'name' => $user->name ?: $consultantOrg->name,
                        'email' => $user->email ?: ($consultantOrg->email ?: null),
                    ]
                );
                $meta['stripe_session_id'] = $session['id'] ?? null;
                $meta['stripe_session_url'] = $session['url'] ?? null;
            } else {
                $cfOrderId = 'consultant_' . $transaction->id . '_' . Str::lower(Str::random(6));
                $returnUrl = route('consultant.packs.payment.cashfree') . '?order_id={order_id}';
                $phone = PaymentService::normalizePhone($user->phone ?? null)
                    ?? PaymentService::normalizePhone($consultantOrg->phone ?? null)
                    ?? PaymentService::normalizePhone(SiteSetting::get('support_phone'))
                    ?? '9999999999';
                $customer = [
                    'id' => 'consultant_' . $consultantOrg->id,
                    'name' => $user->name ?: $consultantOrg->name,
                    'email' => $user->email ?: ($consultantOrg->email ?: 'billing@menetzero.com'),
                    'phone' => $phone,
                ];

                try {
                    $cfOrder = $this->paymentService->createCashfreeOrder(
                        $gateway,
                        $cfOrderId,
                        $transaction->amount,
                        $transaction->currency,
                        $customer,
                        $returnUrl
                    );
                } catch (\RuntimeException $e) {
                    if ($transaction->currency !== 'INR'
                        && $this->paymentService->isCashfreeCurrencyDisabledError($e->getMessage())) {
                        $inrQuote = $inrFallbackQuote();
                        $transaction->update([
                            'amount' => $inrQuote['charge_amount'],
                            'currency' => 'INR',
                        ]);
                        $meta['charged_in_inr_fallback'] = true;
                        $cfOrder = $this->paymentService->createCashfreeOrder(
                            $gateway,
                            $cfOrderId,
                            $inrQuote['charge_amount'],
                            'INR',
                            $customer,
                            $returnUrl
                        );
                        session()->flash('info', 'Charged in INR equivalent while AED activation is pending on Cashfree.');
                    } else {
                        throw $e;
                    }
                }

                $meta['cashfree_order_id'] = $cfOrderId;
                $meta['cashfree_payment_session_id'] = $cfOrder['payment_session_id'] ?? null;
            }

            $transaction->metadata = $meta;
            $transaction->save();

            return redirect()->route('consultant.packs.payment.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }
}
