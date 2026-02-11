@extends('layouts.email')
@section('content')
@php
$orderItems = $order->orderItems;
$imageSrc = "https://truewebcart.s3-accelerate.amazonaws.com/".$preference->shop_logo;
@endphp
<img src="{{$imageSrc}}" alt="{{$shop->shop_name}}" width="200px" style="height: auto"/>
<h2 style="font-size:22px;margin-bottom:8px;text-align:right">
    <strong>Invoice #{{ $order->order_number }}</strong>
</h2>
<h3 style="font-size:18px;font-weight:600;margin-bottom:10px;">Order Summary {{ $shop->shop_name }}</h3>
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:18px;font-size:14px;">
    <thead style="background-color: #f8f8f8;border-bottom: 2px solid #ddd;font-weight: 600">
    <tr>
        <th style="padding: 5px" align="left">Items</th>
        <th style="padding: 5px">Price</th>
        <th style="padding: 5px">Qty</th>
        <th style="padding: 5px">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($orderItems as $item)
        <tr>
            <td style="padding: 5px">
                <div>{{ $item->title }}</div>
            </td>
            <td style="padding: 5px;text-align:right;">
                £{{ number_format($item->price, 2) }}
            </td>
            <td style="padding: 5px;text-align:center;">
                {{ $item->quantity }}
            </td>
            <td style="padding: 5px;text-align:right;">
                £{{ number_format(($item->quantity * $item->price),2) }}
            </td>
        </tr>
    @endforeach

    </tbody>
</table>
<table width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;font-size:14px;border-collapse:collapse;">
    <tr><td>Subtotal</td><td align="right">£{{ number_format($order->subtotal, 2) }}</td></tr>
    <tr><td>Discount</td><td align="right">- £{{ number_format($order->discount_amount, 2) }}</td></tr>
    <tr><td>Shipping</td><td align="right">{{ ($order->shipping_cost ?? 0) > 0 ? '£'.number_format($order->shipping_cost, 2) : 'Free' }}</td></tr>
    <tr><td>Vat at 20%</td><td align="right">£{{ number_format($order->tax_amount, 2) }}</td></tr>
    <tr>
        <td style="padding-top:10px;border-top:2px solid #000;font-weight:700;font-size:16px;">Grand Total (Incl Tax)</td>
        <td align="right" style="padding-top:10px;border-top:2px solid #000;font-weight:700;font-size:16px;">£{{ number_format($order->order_total, 2) }}</td>
    </tr>
</table>
<div style="margin-top:35px;">
    <h3 style="font-size:18px;font-weight:600;margin-bottom:10px;">Customer Information</h3>
    <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
        <tr>
            <td width="50%" valign="top">
                <strong>Shipping Address</strong><br>

                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address_line1 }}<br>
                {{ $order->shipping_address_line2 }}<br>
                {{ $order->shipping_city }}
                {{ $order->shipping_postcode }}<br>
                {{ ($order->shipping_country ?? 'GB') }}
            </td>

            <td width="50%" valign="top">
                <strong>Order Status: </strong> {{ $order->order_status }}<br/>
                <strong>Label Status: </strong>{{ $order->label_status }}<br/>
                <strong>Payment Method: </strong>{{ $order->payment_method }}<br/>
                <strong>Payment Status: </strong>{{ $order->payment_status }}<br/>
                <strong>Fulfillment Status: </strong>{{ $order->fulfillment_status }}<br/>
                <strong>Shipping Method: </strong>{{ $order->shipping_method}}
            </td>
        </tr>
    </table>
</div>
<hr>
<p>
    We’ve attached your invoice for your records.
</p>
@endsection

