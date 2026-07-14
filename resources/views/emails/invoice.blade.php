<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #059669, #3A7D44); padding: 32px 24px; text-align: center; }
        .header h1 { margin: 0; color: #fff; font-size: 24px; font-weight: 800; }
        .header p { color: #d1fae5; margin: 4px 0 0; font-size: 14px; }
        .body { padding: 32px 24px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 12px; }
        .details { background: #f9fafb; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .details td:last-child { text-align: right; font-weight: 700; }
        .total { font-size: 18px; color: #059669; }
        .btn { display: inline-block; background: #059669; color: #fff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-weight: 700; margin-top: 16px; }
        .footer { padding: 20px 24px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Invoice Ready</h1>
            <p>{{ $invoice->invoice_number }}</p>
        </div>
        <div class="body">
            <p>Hi there,</p>
            <p>Your invoice <strong>#{{ $invoice->invoice_number }}</strong> has been generated and is attached to this email.</p>
            <div class="details">
                <table>
                    <tr><td>Invoice No.</td><td>{{ $invoice->invoice_number }}</td></tr>
                    <tr><td>Date</td><td>{{ $invoice->generated_at?->format('F d, Y') ?? now()->format('F d, Y') }}</td></tr>
                    <tr><td>Total Amount</td><td class="total">₱{{ number_format($invoice->total_amount, 2) }}</td></tr>
                    <tr><td>Total Weight</td><td>{{ number_format($invoice->total_kg, 2) }} kg</td></tr>
                    <tr><td>Farms Covered</td><td>{{ $invoice->farm_count }}</td></tr>
                    <tr><td>Due Date</td><td>{{ $invoice->due_at?->format('F d, Y') ?? 'N/A' }}</td></tr>
                </table>
            </div>
            <p style="text-align: center;">
                <a href="{{ url("/invoices/{$invoice->invoice_number}/download") }}" class="btn">Download Invoice PDF</a>
            </p>
            <p style="color: #6b7280; font-size: 13px;">Thank you for using HarvestHaul — Smart Farm Logistics Platform.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} HarvestHaul. All rights reserved.
        </div>
    </div>
</body>
</html>
