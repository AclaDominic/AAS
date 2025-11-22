<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #646cff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #646cff;
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
        .invoice-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .invoice-details h2 {
            margin-top: 0;
            color: #646cff;
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
            background-color: #646cff;
            color: white;
        }
        .total {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
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
        <h1>INVOICE</h1>
    </div>

    <div class="info-section">
        <div class="info-left">
            <strong>Bill To:</strong><br>
            {{ $user->name }}<br>
            {{ $user->email }}
        </div>
        <div class="info-right">
            <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
            <strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('F d, Y') }}<br>
            <strong>Due Date:</strong> {{ $billingStatement->due_date->format('F d, Y') }}
        </div>
    </div>

    <div class="invoice-details">
        <h2>Billing Statement Details</h2>
        <table>
            <tr>
                <th>Description</th>
                <th>Billing Period</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>
                    <strong>{{ $subscription->membershipOffer->name }}</strong><br>
                    Category: {{ str_replace('_', ' ', $subscription->membershipOffer->category) }}<br>
                    @if($subscription->membershipOffer->billing_type === 'RECURRING')
                        Recurring Subscription<br>
                        Duration: {{ $subscription->membershipOffer->duration_value }} {{ strtolower($subscription->membershipOffer->duration_type) }}(s)
                    @else
                        One-time Payment
                    @endif
                </td>
                <td>
                    <strong>From:</strong> {{ $billingStatement->period_start->format('F d, Y') }}<br>
                    <strong>To:</strong> {{ $billingStatement->period_end->format('F d, Y') }}<br>
                    <small style="color: #666;">Statement Date: {{ $billingStatement->statement_date->format('F d, Y') }}</small>
                </td>
                <td>₱{{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </table>
        
        @if($billingStatement->payment)
        <div style="margin-top: 15px; padding: 10px; background-color: #fff3cd; border-radius: 5px;">
            <strong>Payment Information:</strong><br>
            Payment Code: {{ $billingStatement->payment->payment_code ?? 'N/A' }}<br>
            Payment Method: {{ str_replace('_', ' ', $billingStatement->payment->payment_method) }}<br>
            Status: <span style="color: {{ $billingStatement->payment->status === 'PAID' ? '#28a745' : '#ff6b35' }}; font-weight: bold;">{{ $billingStatement->payment->status }}</span>
        </div>
        @endif
        
        <div class="total">
            <strong>Total Amount Due: ₱{{ number_format($invoice->amount, 2) }}</strong><br>
            <small style="color: #666;">Due Date: {{ $billingStatement->due_date->format('F d, Y') }}</small>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated invoice. Please pay by the due date to avoid service interruption.</p>
        <p>Thank you for your business!</p>
    </div>
</body>
</html>

