<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A tax invoice issued against a completed payment. See the create_invoices
 * migration for why this is not folded into PaymentTransaction.
 */
class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'transaction_id',
        'company_id',
        'buyer_name',
        'buyer_email',
        'buyer_address',
        'buyer_trn',
        'seller_name',
        'seller_address',
        'seller_trn',
        'description',
        'currency',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'charged_amount',
        'charged_currency',
        'payment_method',
        'issued_at',
        'pdf_path',
        'line_items',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'charged_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'line_items' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Whether the amount actually taken differs from the amount invoiced --
     * true when the Razorpay AED -> INR fallback fired.
     */
    public function wasChargedInAnotherCurrency(): bool
    {
        return $this->charged_currency !== null
            && strtoupper($this->charged_currency) !== strtoupper((string) $this->currency);
    }

    public function hasTax(): bool
    {
        return (float) $this->tax_amount > 0;
    }

    /**
     * The total spelled out, as a tax invoice is conventionally required to
     * show it ("UAE Dirham Six Thousand Five Hundred Only").
     *
     * NumberFormatter comes from ext-intl, which is not guaranteed to be
     * installed; without it this returns null and the template simply omits the
     * line rather than printing a broken total.
     */
    public function totalInWords(): ?string
    {
        if (!class_exists(\NumberFormatter::class)) {
            return null;
        }

        $currencyNames = [
            'AED' => ['UAE Dirham', 'Fils'],
            'INR' => ['Indian Rupee', 'Paise'],
            'USD' => ['US Dollar', 'Cents'],
        ];

        $code = strtoupper((string) $this->currency);
        [$major, $minor] = $currencyNames[$code] ?? [$code, 'Cents'];

        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);

        $total = round((float) $this->total, 2);
        $whole = (int) floor($total);
        // Rounded before casting: 0.29 * 100 is 28.999... in binary floating
        // point, which truncates to 28 fils instead of 29.
        $fraction = (int) round(($total - $whole) * 100);

        $words = $major . ' ' . ucwords((string) $formatter->format($whole));

        if ($fraction > 0) {
            $words .= ' and ' . ucwords((string) $formatter->format($fraction)) . ' ' . $minor;
        }

        return $words . ' Only';
    }
}
