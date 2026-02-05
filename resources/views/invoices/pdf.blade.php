<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .invoice-info {
            margin-bottom: 30px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 5px 0;
        }
        .invoice-info .label {
            font-weight: bold;
            width: 150px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #4F46E5;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 30px;
            text-align: right;
        }
        .total-row {
            margin: 5px 0;
            font-size: 16px;
        }
        .total-row.grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #4F46E5;
            padding-top: 10px;
            border-top: 2px solid #4F46E5;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p>MDI Warehouse Management System</p>
    </div>

    <div class="invoice-info">
        <table>
            <tr>
                <td class="label">Invoice Number:</td>
                <td><strong>{{ $sale->invoice_number }}</strong></td>
                <td class="label">Date:</td>
                <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Customer Name:</td>
                <td>{{ $sale->customer_name }}</td>
                <td class="label">Status:</td>
                <td><strong style="text-transform: uppercase;">{{ $sale->status }}</strong></td>
            </tr>
            <tr>
                <td class="label">Phone:</td>
                <td>{{ $sale->customer_phone ?? '-' }}</td>
                <td class="label">Sales Person:</td>
                <td>{{ $sale->salesPerson->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td>{{ $sale->customer_email ?? '-' }}</td>
                <td></td>
                <td></td>
            </tr>
            @if($sale->customer_address)
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $sale->customer_address }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Product</th>
                <th style="width: 15%;" class="text-right">Quantity</th>
                <th style="width: 17%;" class="text-right">Unit Price</th>
                <th style="width: 18%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->title ?? $item->product->name }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row grand-total">
            TOTAL: Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
        </div>
    </div>

    @if($sale->notes)
    <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #4F46E5;">
        <strong>Notes:</strong><br>
        {{ $sale->notes }}
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p style="margin-top: 10px;">Generated on {{ now()->format('d F Y - H:i') }}</p>
    </div>
</body>
</html>
