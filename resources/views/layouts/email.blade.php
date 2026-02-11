<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order / Invoice Details</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 30px;
            color: #333;
        }
        .invoice-container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #333;
        }
        .header p {
            color: #777;
            font-size: 14px;
            margin: 5px 0 0;
        }
        .summary {
            margin-bottom: 25px;
        }
        .summary p {
            font-size: 15px;
            margin: 4px 0;
            color: #555;
        }
        .summary strong {
            color: #000;
        }
        .divider {
            border-top: 2px solid #eee;
            margin: 25px 0;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
        }
        .footer {
            text-align: center;
            border-top: 1px solid #eee;
            margin-top: 30px;
            padding-top: 20px;
            font-size: 13px;
            color: #777;
        }
        .footer a {
            color: #008060;
            text-decoration: none;
        }
        .btn {
            display: inline-block;
            background-color: #008060;
            color: #fff;
            padding: 12px 22px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 15px;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #00684a;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 10px; }
            .invoice-container { padding: 20px; }
        }
    </style>
</head>
<body>
<div style="margin: 0 auto;max-width: 600px;">
    @yield('content')
</div>
</body>
</html>
