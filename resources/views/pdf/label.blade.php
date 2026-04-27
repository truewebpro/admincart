<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        .box { border: 1px solid #000; padding: 15px; }
    </style>
</head>
<body>

<div class="box">
    <h3>Shipping Label</h3>

    <p><strong>Order:</strong> #{{ $order->order_id }}</p>

    <p>
        {{ $order->shipping_name }}<br>
        {{ $order->address }}<br>
        {{ $order->city }} - {{ $order->pincode }}<br>
        {{ $order->country }}<br>
        Phone: {{ $order->phone }}
    </p>

    <p><strong>Courier:</strong> {{ $order->shipping_method }}</p>
</div>

</body>
</html>
