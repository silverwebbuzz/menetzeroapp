<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Issues tax invoices for completed payments.
 *
 * Called from PaymentCompletionService, which is the single point every
 * payment type passes through -- client subscriptions and all four consultant
 * agency types -- so one call site covers every package.
 */
class InvoiceService
{
    /** Invoices live on the private disk: they contain buyer details. */
    protected const DISK = 'local';

    /**
     * Issue an invoice for a completed transaction, or return the existing one.
     *
     * Idempotent by transaction_id. A gateway that delivers both a callback and
     * a webhook completes the same transaction twice, and the second pass must
     * not spend another invoice number.
     */
    public function issueFor(PaymentTransaction $transaction): ?Invoice
    {
        if ($transaction->status !== 'completed') {
            return null;
        }

        $existing = Invoice::where('transaction_id', $transaction->id)->first();
        if ($existing) {
            return $existing;
        }

        $invoice = $this->createRecord($transaction);

        // A PDF failure must not lose the invoice: the record is already
        // committed and the number spent. Render failures are logged and the
        // PDF is regenerated on demand when the buyer downloads it.
        try {
            $this->renderPdf($invoice);
        } catch (\Throwable $e) {
            Log::error('Invoice PDF generation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $invoice;
    }

    protected function createRecord(PaymentTransaction $transaction): Invoice
    {
        $company = $transaction->company_id ? Company::find($transaction->company_id) : null;
        $meta = $transaction->metadata ?? [];

        // The quoted price and currency. When the Razorpay INR fallback fires,
        // amount/currency are overwritten with the INR settlement and the
        // original quote survives only in metadata.
        //
        // Two key spellings on purpose: the client checkout already wrote
        // original_amount/original_currency before this feature existed, and
        // renaming live keys would strand transactions mid-flight. The
        // consultant path writes quoted_amount/quoted_currency.
        $quotedAmount = (float) ($meta['quoted_amount'] ?? $meta['original_amount'] ?? $transaction->amount);
        $quotedCurrency = strtoupper((string) (
            $meta['quoted_currency'] ?? $meta['original_currency'] ?? $transaction->currency ?: 'AED'
        ));
        $chargedCurrency = strtoupper((string) ($transaction->currency ?: $quotedCurrency));

        $taxRate = $this->taxRate();

        // The quoted figure is treated as TAX-INCLUSIVE: it is the price the
        // customer was shown and agreed to. Adding tax on top would charge
        // more than the checkout displayed.
        $subtotal = $taxRate > 0
            ? round($quotedAmount / (1 + ($taxRate / 100)), 2)
            : round($quotedAmount, 2);
        $taxAmount = round($quotedAmount - $subtotal, 2);

        $attributes = [
            'transaction_id' => $transaction->id,
            'company_id' => $transaction->company_id,
            'buyer_name' => $company?->name ?? 'Customer',
            'buyer_email' => $company?->email,
            'buyer_address' => $this->companyAddress($company),
            // companies has no TRN column. The buyer's TRN is captured on the
            // invoice itself (editable in admin) rather than inventing a
            // schema change here; a UAE tax invoice needs the buyer TRN only
            // when they are VAT-registered and ask for it.
            'buyer_trn' => data_get($company?->settings, 'trn'),
            'seller_name' => SiteSetting::get('billing_legal_name', config('app.name', 'MENetZero')),
            'seller_address' => SiteSetting::get('billing_address'),
            'seller_trn' => SiteSetting::get('billing_trn'),
            'description' => $transaction->description ?: 'Subscription',
            'currency' => $quotedCurrency,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($quotedAmount, 2),
            'charged_amount' => $chargedCurrency !== $quotedCurrency ? (float) $transaction->amount : null,
            'charged_currency' => $chargedCurrency !== $quotedCurrency ? $chargedCurrency : null,
            'payment_method' => $transaction->payment_method,
            'issued_at' => $transaction->paid_at ?? now(),
            'line_items' => [[
                'description' => $transaction->description ?: 'Subscription',
                'quantity' => (int) ($meta['slots'] ?? 1),
                'total' => round($quotedAmount, 2),
            ]],
        ];

        return $this->createWithNumber($attributes);
    }

    /**
     * Allocate the next number and insert, retrying on a duplicate.
     *
     * Two payments completing at the same instant can read the same MAX() and
     * both try to claim it. The unique index on invoice_number turns that into
     * an exception rather than a duplicate document; the retry then reads the
     * committed row and takes the next one.
     */
    protected function createWithNumber(array $attributes): Invoice
    {
        $prefix = 'INV-' . now()->format('Y') . '-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $next = $this->nextSequence($prefix);
            $attributes['invoice_number'] = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

            try {
                return Invoice::create($attributes);
            } catch (\Illuminate\Database\QueryException $e) {
                if (!$this->isDuplicateKey($e) || $attempt === 4) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Could not allocate an invoice number.');
    }

    protected function nextSequence(string $prefix): int
    {
        $last = DB::table('invoices')
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        if (!$last) {
            return 1;
        }

        return ((int) substr($last, strlen($prefix))) + 1;
    }

    protected function isDuplicateKey(\Illuminate\Database\QueryException $e): bool
    {
        // 23000 covers MySQL's integrity-constraint violations, of which a
        // duplicate unique key (errno 1062) is the one expected here.
        return in_array($e->getCode(), ['23000', '23505'], true);
    }

    /**
     * Render and store the PDF, returning the storage path.
     */
    public function renderPdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'dejavu sans',
            ]);

        $path = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk(self::DISK)->put($path, $pdf->output());

        $invoice->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    /**
     * The stored PDF, rendering it first if it is missing.
     */
    public function pdfContents(Invoice $invoice): string
    {
        if (!$invoice->pdf_path || !Storage::disk(self::DISK)->exists($invoice->pdf_path)) {
            $this->renderPdf($invoice);
            $invoice->refresh();
        }

        return Storage::disk(self::DISK)->get($invoice->pdf_path);
    }

    /**
     * VAT rate. Defaults to 0 -- a rate is only applied once a TRN is recorded
     * in admin, because charging VAT without being registered to collect it is
     * a compliance problem, not a display detail.
     */
    protected function taxRate(): float
    {
        if (blank(SiteSetting::get('billing_trn'))) {
            return 0.0;
        }

        return (float) SiteSetting::get('billing_vat_rate', 0);
    }

    protected function companyAddress(?Company $company): ?string
    {
        if (!$company) {
            return null;
        }

        $parts = array_filter([
            $company->address ?? null,
            $company->city ?? null,
            $company->country ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
