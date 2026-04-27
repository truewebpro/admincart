<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>

<h2>Invoice #{{ $order->order_id }}</h2>
<h2>Invoice #{{ $order->order_number }}</h2>

<p>
    <strong>Ship to:</strong>
    {{ $order->shipping_name }}<br>
    {{ $order->shipping_address_line1 }}<br>
    {{ $order->shipping_address_line2 }}<br>
    {{ $order->shipping_city }}, {{ $order->shipping_postcode }}<br>
    {{ $order->shipping_country }}<br>
    {{ $order->shipping_phone }}
</p>

<table>
    <thead>
    <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($order->items as $item)
        <tr>
            <td>{{ $item->title }} @foreach($item->options as $opts)<span>{{$opts['name']}} , {{$opts['value']}}</span> @endforeach</td>
            <td>{{ $item->quantity }}</td>
            <td>£{{ $item->price }}</td>
            <td>£{{ $item->quantity * $item->price }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="3">Total</td>
        <td>£{{ $order->order_total }}</td>
    </tr>
    </tbody>
</table>

<h3>Total: £{{ $order->order_total }}</h3>

</body>
</html>
