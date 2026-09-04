{{--
    Tax invoice PDF. Rendered by InvoiceService::renderPdf() through dompdf,
    so this uses table layout and inline styles only -- dompdf has no flexbox
    or grid support.

    Every figure comes from the invoice row, never recomputed here: the record
    is the document, and a total that changed between issue and reprint would
    be a different invoice. The only things read from settings are the seller's
    own details, which are presentation rather than accounting facts.
--}}
@php
    use App\Models\SiteSetting;

    // MENetZero is the trading brand; the invoice must still name the legal
    // entity that is party to the sale, so both appear -- brand large, legal
    // entity beneath it. seller_name is whatever was stamped on the invoice
    // when it was issued, so a later change of legal name cannot rewrite it.
    $brand = SiteSetting::get('billing_brand_name') ?: config('app.name', 'MENetZero');
    $registrationNo = SiteSetting::get('billing_registration_no');
    $billingEmail = SiteSetting::get('billing_email') ?: SiteSetting::get('support_email');
    $billingPhone = SiteSetting::get('billing_phone');
    $terms = SiteSetting::get('billing_terms');
    $hsn = SiteSetting::get('billing_hsn_sac');

    // dompdf renders only simple SVG (paths with solid fills). The logo is
    // exactly that, but a failed decode would blow up the whole PDF -- and an
    // invoice that will not render is worse than one without a logo -- so it is
    // read defensively and dropped on any error.
    $logoData = null;
    try {
        $logoPath = public_path('images/menetzero.svg');
        if (is_readable($logoPath) && filesize($logoPath) < 200000) {
            $logoData = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
        }
    } catch (\Throwable $e) {
        $logoData = null;
    }

    $lines = $invoice->line_items ?: [[
        'description' => $invoice->description,
        'quantity' => 1,
        'total' => $invoice->total,
    ]];

    // An invoice is only issued once payment has completed, so it is a receipt
    // as much as a demand. Saying so stops customers paying a second time.
    $isPaid = $invoice->issued_at !== null;
    $totalWords = $invoice->totalInWords();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'dejavu sans', sans-serif; font-size: 11px; color: #14161a; margin: 0; }
        .wrap { padding: 34px 38px 90px; }

        .head { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .head td { vertical-align: top; }
        .logo { height: 34px; }
        .brand { font-size: 19px; font-weight: bold; letter-spacing: -0.3px; }
        .seller-legal { font-weight: bold; margin-top: 8px; }
        .doc-title { font-size: 22px; font-weight: bold; letter-spacing: -0.4px; text-align: right; }
        .paid-badge { display: inline-block; margin-top: 8px; padding: 3px 10px; border-radius: 3px;
                      background: #e7f5ec; color: #1c7a43; font-size: 10px; font-weight: bold;
                      letter-spacing: 0.6px; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 20px;
                border-top: 1px solid #d6d7d3; border-bottom: 1px solid #d6d7d3; }
        .meta td { padding: 9px 12px 9px 0; vertical-align: top; width: 25%; }
        .meta .k { font-size: 9px; text-transform: uppercase; letter-spacing: 0.7px; color: #8b9199; }
        .meta .v { font-weight: bold; margin-top: 2px; }

        .party { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .party td { vertical-align: top; width: 50%; padding-right: 18px; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.7px;
                 color: #8b9199; padding-bottom: 5px; }
        .name { font-weight: bold; }
        .muted { color: #5a6068; line-height: 1.5; }

        table.items { width: 100%; border-collapse: collapse; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.7px;
                         color: #ffffff; background: #14161a; padding: 8px 10px; }
        table.items td { padding: 10px; border-bottom: 1px solid #ececea; vertical-align: top; }
        table.items tr.alt td { background: #fafaf9; }
        .r { text-align: right; }
        .c { text-align: center; }

        .totals { width: 46%; margin-left: 54%; border-collapse: collapse; margin-top: 12px; }
        .totals td { padding: 6px 10px; }
        .totals .grand td { border-top: 2px solid #14161a; font-weight: bold;
                           font-size: 13px; padding-top: 9px; }
        .totals .due td { background: #f2f7f4; font-weight: bold; }

        .words { margin-top: 16px; font-size: 10px; }
        .words .label { display: block; }
        .note { margin-top: 18px; padding: 10px 12px; background: #f7f7f5;
                border-left: 3px solid #d6d7d3; font-size: 10px; color: #5a6068; line-height: 1.5; }
        .terms { margin-top: 20px; font-size: 10px; color: #5a6068; line-height: 1.5; }
        .terms .label { display: block; }

        .foot { position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 38px 22px;
                border-top: 1px solid #e5e6e3; font-size: 9px; color: #8b9199; }
    </style>
</head>
<body>
<div class="wrap">

    <table class="head">
        <tr>
            <td style="width:58%">
                @if ($logoData)
                    <img src="{{ $logoData }}" class="logo" alt="{{ $brand }}">
                @else
                    <div class="brand">{{ $brand }}</div>
                @endif

                {{-- The legal entity behind the brand. Both are needed: the
                     customer knows the product by its brand, but the contract
                     and the tax point belong to the company. --}}
                <div class="seller-legal">{{ $invoice->seller_name }}</div>
                @if ($invoice->seller_address)
                    <div class="muted">{!! nl2br(e($invoice->seller_address)) !!}</div>
                @endif
                @if ($invoice->seller_trn)
                    <div class="muted">TRN {{ $invoice->seller_trn }}</div>
                @endif
                @if ($registrationNo)
                    <div class="muted">Reg. No. {{ $registrationNo }}</div>
                @endif
            </td>
            <td style="width:42%">
                <div class="doc-title">Tax Invoice</div>
                @if ($isPaid)
                    <div class="r"><span class="paid-badge">PAID</span></div>
                @endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="k">Invoice no.</div>
                <div class="v">{{ $invoice->invoice_number }}</div>
            </td>
            <td>
                <div class="k">Invoice date</div>
                <div class="v">{{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}</div>
            </td>
            <td>
                <div class="k">Terms</div>
                {{-- Issued only after payment clears, so it is never on credit. --}}
                <div class="v">{{ $isPaid ? 'Paid in full' : 'Due on receipt' }}</div>
            </td>
            <td>
                <div class="k">Amount</div>
                <div class="v">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="party">
        <tr>
            <td>
                <div class="label">Billed to</div>
                <div class="name">{{ $invoice->buyer_name }}</div>
                @if ($invoice->buyer_address)
                    <div class="muted">{!! nl2br(e($invoice->buyer_address)) !!}</div>
                @endif
                @if ($invoice->buyer_email)
                    <div class="muted">{{ $invoice->buyer_email }}</div>
                @endif
                @if ($invoice->buyer_trn)
                    <div class="muted">TRN {{ $invoice->buyer_trn }}</div>
                @endif
            </td>
            <td>
                <div class="label">Payment</div>
                <div class="muted" style="text-transform:capitalize">
                    {{ $invoice->payment_method ?: 'Online' }}
                </div>
                @if ($isPaid)
                    <div class="muted">Received {{ $invoice->issued_at->format('d/m/Y') }}</div>
                @endif
                @if ($billingEmail)
                    <div class="label" style="padding-top:12px">Billing queries</div>
                    <div class="muted">{{ $billingEmail }}</div>
                @endif
                @if ($billingPhone)
                    <div class="muted">{{ $billingPhone }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <tr>
            <th style="width:26px">#</th>
            <th>Item &amp; description</th>
            @if ($hsn)
                <th class="c" style="width:70px">HSN/SAC</th>
            @endif
            <th class="c" style="width:44px">Qty</th>
            <th class="r" style="width:82px">Rate</th>
            <th class="r" style="width:92px">Amount</th>
        </tr>
        @foreach ($lines as $i => $line)
            @php
                $qty = (float) ($line['quantity'] ?? 1);
                $amount = (float) ($line['total'] ?? 0);
                // Rate is derived for display only; the amount is the figure of
                // record. Guarded because a zero quantity would divide by zero.
                $rate = $qty > 0 ? $amount / $qty : $amount;
            @endphp
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $line['description'] ?? $invoice->description }}</td>
                @if ($hsn)
                    <td class="c">{{ $line['hsn_sac'] ?? $hsn }}</td>
                @endif
                <td class="c">{{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}</td>
                <td class="r">{{ number_format($rate, 2) }}</td>
                <td class="r">{{ number_format($amount, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Sub total</td>
            <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        @if ($invoice->hasTax())
            <tr>
                <td class="muted">VAT ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2), '0'), '.') }}%)</td>
                <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>Total</td>
            <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
        <tr class="due">
            <td>{{ $isPaid ? 'Amount paid' : 'Balance due' }}</td>
            <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
    </table>

    @if ($totalWords)
        <div class="words">
            <span class="label">Total in words</span>
            <strong>{{ $totalWords }}</strong>
        </div>
    @endif

    {{-- Shown only when Razorpay's AED -> INR fallback fired. The invoice is
         denominated in the currency that was quoted; this states what actually
         reached the card so the figures reconcile to a bank statement. --}}
    @if ($invoice->wasChargedInAnotherCurrency())
        <div class="note">
            Charged as <strong>{{ $invoice->charged_currency }}
            {{ number_format((float) $invoice->charged_amount, 2) }}</strong> via
            {{ $invoice->payment_method ?: 'the payment gateway' }}. The invoice is
            denominated in {{ $invoice->currency }}; the settlement currency differs
            because of the payment provider's supported currencies.
        </div>
    @endif

    @unless ($invoice->hasTax())
        <div class="note">
            No VAT has been charged on this invoice.
        </div>
    @endunless

    @if ($terms)
        <div class="terms">
            <span class="label">Terms &amp; conditions</span>
            {!! nl2br(e($terms)) !!}
        </div>
    @endif

</div>

<div class="foot">
    {{ $invoice->invoice_number }} · {{ $invoice->seller_name }}
    @if ($invoice->seller_trn) · TRN {{ $invoice->seller_trn }} @endif
    @if ($billingEmail) · {{ $billingEmail }} @endif
    · This is a computer-generated invoice and does not require a signature.
</div>
</body>
</html>
