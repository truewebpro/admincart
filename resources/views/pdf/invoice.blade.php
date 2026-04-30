<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            width: 100%;
            margin-bottom: 10px;
        }

        .header td {
            vertical-align: top;
        }

        .company {
            line-height: 1.6;
        }

        .right {
            text-align: right;
        }

        .box {
            border: 0 solid #ddd;
            padding: 10px;
            width: 48%;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
        }

        table.items th, table.items td {
            border: 1px solid #ddd;
            padding: 4px;
        }

        table.items th {
            background: #f5f5f5;
        }

        .totals {
            width: 300px;
            float: right;
            margin-top: 10px;
        }

        .totals td {
            padding: 4px;
        }

        .totals tr td:first-child,.totals tr td:last-child {
            text-align: right;
        }

        .footer {
            margin-top: 40px;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
<!-- HEADER -->
<table class="header">
    <tr>
        <td width="40%">
            <img src="https://cdn.truewebcart.com/{{ $preference->shop_logo }}" alt="{{$shop->shop_name}}" height="48">
            <div>
                <h2>INVOICE</h2>
                <strong>Order #{{ $order->order_id }}</strong><br>
                <strong>Invoice #{{ $order->order_number }}</strong><br>
                Date: {{ $order->created_at->format('d M Y H:i') }}<br>
                Payment: {{ $order->payment_method }}
            </div>
        </td>
        <td width="60%" class="right">
            <div class="company">
                <strong>{{ $business->business_name }}</strong>, {{ $business->address_line1 }}<br>
                {{ $business->address_line2 }}, {{ $business->region }} {{ $business->postcode }}<br>
                Company No: {{ $business->reg_no }} / VAT No: {{ $business->vat_no }}<br>
                Phone No: {{ $business->phone }} <br>
                {{ $business->email }} <br>
                {{ $shop->maindomain }} <br>
            </div>
        </td>
    </tr>
</table>

<!-- BILL / SHIP -->
<table width="100%" style="margin-bottom:10px;">
    <tr>
        <td class="box">
            <strong>Bill To</strong><br>
            {{ $order->shipping_name }}<br>
            {{ $order->shipping_address_line1 }}<br>
            {{ $order->shipping_address_line2 }}<br>
            {{ $order->shipping_city }}, {{ $order->shipping_postcode }}<br>
            {{ $order->shipping_country }}<br>
            {{ $order->shipping_phone }}
        </td>

        <td width="4%"></td>

        <td class="box">
            <strong>Ship To</strong><br>
            {{ $order->shipping_name }}<br>
            {{ $order->shipping_address_line1 }}<br>
            {{ $order->shipping_address_line2 }}<br>
            {{ $order->shipping_city }}, {{ $order->shipping_postcode }}<br>
            {{ $order->shipping_country }}<br>
            {{ $order->shipping_phone }}
        </td>
    </tr>
</table>
<table class="items">
    <thead>
    <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>VAT</th>
        <th>Unit Price</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($order->items as $item)
        <tr>
            <td>{{ $item->title }} @foreach($item->options as $opts)<span>{{$opts['name']}} , {{$opts['value']}}</span> @endforeach</td>
            <td>{{ $item->quantity }}</td>
            <td>20%</td>
            <td>£{{ number_format($item->price, 2) }}</td>
            <td>£{{ number_format($item->quantity * $item->price, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<table class="totals">
    <tr>
        <td><strong>Sub Total:</strong></td>
        <td>£{{ number_format($order->subtotal, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Shipping:</strong></td>
        <td>£{{ number_format($order->shipping_cost, 2) }}</td>
    </tr>
    <tr>
        <td><strong>VAT (20%):</strong></td>
        <td>£{{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Discount Amount:</strong></td>
        <td>£{{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Total:</strong></td>
        <td><strong>£{{ number_format($order->order_total, 2) }}</strong></td>
    </tr>
    <tr>
        <td>Paid:</td>
        <td>£{{ number_format($order->order_total, 2) }}</td>
    </tr>
    <tr>
        <td>Amount Due:</td>
        <td>£{{ number_format($order->order_total - $order->order_total, 2) }}</td>
    </tr>
</table>

<div style="clear: both;"></div>

<!-- FOOTER -->
<div class="footer">
    Thank you for your business.
</div>
</body>
</html>
