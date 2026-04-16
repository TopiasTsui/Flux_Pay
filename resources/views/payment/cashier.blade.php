<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .order-info { margin-bottom: 20px; }
        .order-info .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .order-info .label { color: #666; }
        .order-info .value { color: #333; font-weight: bold; }
        .amount { font-size: 24px; text-align: center; color: #2d8cf0; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Page</h1>
        <div class="amount">{{ $order->currency }} {{ number_format((float) $order->order_amount, 2) }}</div>
        <div class="order-info">
            <div class="row">
                <span class="label">Order No</span>
                <span class="value">{{ $order->system_order_no }}</span>
            </div>
            <div class="row">
                <span class="label">Merchant</span>
                <span class="value">{{ $merchant->name }}</span>
            </div>
            <div class="row">
                <span class="label">Status</span>
                <span class="value">{{ $order->status->label() }}</span>
            </div>
        </div>
    </div>
</body>
</html>
