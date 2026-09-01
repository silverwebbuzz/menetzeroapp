{{--
    Tax invoice PDF. Rendered by InvoiceService::renderPdf() through dompdf,
    so this uses table layout and inline styles only -- dompdf has no flexbox
    or grid support.

    Every figure comes from the invoice row, never recomputed here: the record
    is the document, and a total that changed between issue and reprint would
    be a different invoice.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'dejavu sans', sans-serif; font-size: 12px; color: #14161a; margin: 0; }
        .wrap { padding: 36px 40px; }
        .head { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .head td { vertical-align: top; }
        .title { font-size: 24px; font-weight: bold; letter-spacing: -0.5px; }
        .num { font-size: 12px; color: #5a6068; margin-top: 4px; }
        .party { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .party td { vertical-align: top; width: 50%; padding-right: 16px; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #8b9199; padding-bottom: 5px; }
        .name { font-weight: bold; }
        .muted { color: #5a6068; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px;
                         color: #8b9199; border-bottom: 1px solid #d6d7d3; padding: 8px 0; }
        table.items td { padding: 10px 0; border-bottom: 1px solid #f0f0ee; }
        .r { text-align: right; }
        .totals { width: 45%; margin-left: 55%; border-collapse: collapse; margin-top: 14px; }
        .totals td { padding: 6px 0; }
        .totals .grand td { border-top: 2px solid #14161a; font-weight: bold; font-size: 14px; padding-top: 10px; }
        .note { margin-top: 26px; padding: 12px 14px; background: #f7f7f5; border-radius: 6px;
                font-size: 11px; color: #5a6068; }
        .foot { margin-top: 34px; padding-top: 12px; border-top: 1px solid #e5e6e3;
                font-size: 10px; color: #8b9199; }
    </style>
</head>
<body>
<div class="wrap">

    <table class="head">
        <tr>
            <td>
                <div class="title">Tax Invoice</div>
                <div class="num">{{ $invoice->invoice_number }}</div>
            </td>
            <td class="r">
                <div class="name">{{ $invoice->seller_name }}</div>
                @if ($invoice->seller_address)
                    <div class="muted">{{ $invoice->seller_address }}</div>
                @endif
                @if ($invoice->seller_trn)
                    <div class="muted">TRN {{ $invoice->seller_trn }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="party">
        <tr>
            <td>
                <div class="label">Billed to</div>
                <div class="name">{{ $invoice->buyer_name }}</div>
                @if ($invoice->buyer_address)
                    <div class="muted">{{ $invoice->buyer_address }}</div>
                @endif
                @if ($invoice->buyer_email)
                    <div class="muted">{{ $invoice->buyer_email }}</div>
                @endif
                @if ($invoice->buyer_trn)
                    <div class="muted">TRN {{ $invoice->buyer_trn }}</div>
                @endif
            </td>
            <td>
                <div class="label">Issued</div>
                <div>{{ $invoice->issued_at?->format('d F Y') ?? '—' }}</div>
                @if ($invoice->payment_method)
                    <div class="label" style="padding-top:12px">Paid via</div>
                    <div style="text-transform:capitalize">{{ $invoice->payment_method }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <tr>
            <th>Description</th>
            <th class="r" style="width:70px">Qty</th>
            <th class="r" style="width:110px">Amount</th>
        </tr>
        @foreach ($invoice->line_items ?? [] as $line)
            <tr>
                <td>{{ $line['description'] ?? $invoice->description }}</td>
                <td class="r">{{ $line['quantity'] ?? 1 }}</td>
                <td class="r">{{ number_format((float) ($line['total'] ?? 0), 2) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal</td>
            <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="muted">VAT ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2), '0'), '.') }}%)</td>
            <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->tax_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="r">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
    </table>

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

    <div class="foot">
        {{ $invoice->invoice_number }} · Issued {{ $invoice->issued_at?->format('d M Y') ?? '' }}
        @if ($invoice->seller_name) · {{ $invoice->seller_name }} @endif
    </div>

</div>
</body>
</html>
