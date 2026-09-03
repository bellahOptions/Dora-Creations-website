<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 22mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #2a2620;
            margin: 0;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 26px;
        }

        .header .brand {
            display: table-cell;
            vertical-align: top;
        }

        .header .brand .name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header .brand .tagline {
            font-size: 9px;
            color: #8a8171;
            margin-top: 2px;
        }

        .header .meta {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }

        .header .meta .title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header .meta .row {
            font-size: 9.5px;
            color: #4a4438;
            margin-top: 3px;
        }

        .parties {
            display: table;
            width: 100%;
            margin-bottom: 22px;
        }

        .parties .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .parties .label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a29a89;
            margin-bottom: 4px;
        }

        .parties .line {
            font-size: 10.5px;
            line-height: 1.5;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.items th {
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a29a89;
            border-bottom: 1px solid #e7e4de;
            padding: 0 0 6px;
        }

        table.items td {
            font-size: 10.5px;
            padding: 8px 0;
            border-bottom: 1px solid #f1efe9;
            vertical-align: top;
        }

        table.items th.num, table.items td.num {
            text-align: right;
        }

        .item-name {
            font-weight: bold;
        }

        .item-variant {
            color: #8a8171;
            font-size: 9px;
            margin-top: 1px;
        }

        table.totals {
            width: 100%;
            margin-top: 12px;
        }

        table.totals td {
            padding: 3px 0;
            font-size: 10.5px;
        }

        table.totals td.label {
            color: #6b6355;
        }

        table.totals td.value {
            text-align: right;
        }

        table.totals tr.total td {
            border-top: 1px solid #2a2620;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .totals-wrap {
            display: table;
            width: 100%;
        }

        .totals-wrap .spacer {
            display: table-cell;
            width: 55%;
        }

        .totals-wrap .figures {
            display: table-cell;
            width: 45%;
        }

        .footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #e7e4de;
            text-align: center;
            font-size: 9px;
            color: #a29a89;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div class="name">Dora Creations</div>
            <div class="tagline">Nigerian-made fashion</div>
        </div>
        <div class="meta">
            <div class="title">Receipt</div>
            <div class="row">Order {{ $order->order_number }}</div>
            <div class="row">{{ $order->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

    <div class="parties">
        <div class="col">
            <div class="label">Billed to</div>
            <div class="line">{{ $order->customerName() }}</div>
            <div class="line">{{ $order->customerEmail() }}</div>
            <div class="line">{{ $order->shipping_phone }}</div>
        </div>
        <div class="col">
            <div class="label">Delivery address</div>
            <div class="line">{{ $order->shipping_line1 }}</div>
            @if ($order->shipping_line2)
                <div class="line">{{ $order->shipping_line2 }}</div>
            @endif
            <div class="line">{{ $order->shipping_city }}, {{ $order->shipping_state }}, {{ $order->shipping_country }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Unit price</th>
                <th class="num">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->product_name }}</div>
                        @if ($item->variant_label)
                            <div class="item-variant">{{ $item->variant_label }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ \App\Support\Money::ngn($item->unit_price_kobo) }}</td>
                    <td class="num">{{ $item->formattedLineTotal() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-wrap">
        <div class="spacer"></div>
        <div class="figures">
            <table class="totals">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ $order->formattedSubtotal() }}</td>
                </tr>
                @if ($order->discount_kobo > 0)
                    <tr>
                        <td class="label">Discount{{ $order->discount_code ? " ({$order->discount_code})" : '' }}</td>
                        <td class="value">-{{ $order->formattedDiscount() }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Shipping</td>
                    <td class="value">{{ $order->formattedShipping() }}</td>
                </tr>
                <tr class="total">
                    <td class="label">Total</td>
                    <td class="value">{{ $order->formattedTotal() }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Dora Creations &middot; {{ config('app.url') }} &middot; Generated {{ now()->format('d M Y, H:i') }}
    </div>
</body>
</html>
