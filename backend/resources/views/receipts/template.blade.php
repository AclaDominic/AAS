<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-right {
            text-align: right;
        }
        .receipt-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .receipt-details h2 {
            margin-top: 0;
            color: #28a745;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #28a745;
            color: white;
        }
        .total {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PAYMENT RECEIPT</h1>
    </div>

    <div class="info-section">
        <div class="info-left">
            <strong>Paid By:</strong><br>
            {{ $user->name }}<br>
            {{ $user->email }}
        </div>
        <div class="info-right">
            <strong>Receipt Number:</strong> {{ $receipt->receipt_number }}<br>
            <strong>Receipt Date:</strong> {{ $receipt->receipt_date->format('F d, Y') }}<br>
            <strong>Payment Date:</strong> {{ $payment->payment_date ? $payment->payment_date->format('F d, Y h:i A') : 'N/A' }}
        </div>
    </div>

    <div class="receipt-details">
        <h2>Payment Details</h2>
        <table>
            <tr>
                <th>Description</th>
                <th>Payment Method</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>
                    <strong>{{ $payment->membershipOffer->name }}</strong><br>
                    Category: {{ $payment->membershipOffer->category }}
                    @if($payment->promo)
                        <br>Promo: {{ $payment->promo->name }}
                    @endif
                    @if($payment->first_time_discount)
                        <br>Discount: {{ $payment->firstTimeDiscount->name }}
                    @endif
                </td>
                <td>{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                <td>₱{{ number_format($receipt->amount, 2) }}</td>
            </tr>
        </table>
        <div class="total">
            <span class="status">PAID</span><br><br>
            <strong>Total Amount Paid: ₱{{ number_format($receipt->amount, 2) }}</strong>
        </div>
    </div>

    <div class="footer">
        <p>This is an official receipt for your payment. Please keep this for your records.</p>
        <p>Thank you for your payment!</p>
    </div>
</body>
</html>

