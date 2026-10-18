<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur {{ $sale->invoice_number }}</title>
    @vite (['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: Arial, sans-serif;
            width: 8.5in;
            height: 11in;
            margin: 0 auto;
            padding: 20px;
            print-color-adjust: exact;
            print: {
                size: A4;
                margin: 0;
            }
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
            @page { size: letter; margin: 0; }
            @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="mx-auto bg-white shadow-lg w-204 h-264 p-12 print:w-204 print:h-264 print:p-12 print:shadow-none print:mx-0">
        
        <!-- Invoice Header -->
        <div class="flex items-center justify-between border-b border-blue-900 p-0" style="margin-bottom: 20px; padding-bottom: -12%; border-bottom: 1px solid #1c398e;">
            <div>
              <!-- Your Logo Perusahaan (Optional) -->
              <img src="./public/assets/static/kop/header mdi kiri.png" alt="Logo Perusahaan" class="flex items-start justify-between relative w-28 " style="margin-left: 0px; margin-right: 24.5%;">
              <img src="./public/assets/static/kop/header mdi kanan.png" alt="Logo Perusahaan" class="flex items-end justify-between relative w-72" style="margin-bottom: 10px; width: 450px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 350px; margin-bottom: 24px;">
            <div>
                <p style="font-weight: bold; font-size: 16px;">Kepada:</p>
                <p style="font-size: 14px; margin-left: 40px; text-decoration-line: underline; font-style: italic;">{{ $sale->customer_name }}</p>
                <p style="font-size: 12px; color: #666; margin-left: 40px; margin-top: 2px;">{{ $sale->customer_address }}</p>
                <p style="font-size: 12px; color: #666; margin-left: 40px; margin-top: 2px;">{{ $sale->customer_phone }}</p>
                <p style="font-size: 12px; color: #666; margin-left: 40px; margin-top: 2px;">{{ $sale->customer_email }}</p>
            </div>
            <div>
                <div class="space-y-1 text-[12px] p-3.5" style="margim-bottom: 4px; background-color: #f3f4f6; padding-top: 12px;">
                    <div class="flex justify-between" style="margin-bottom: 4px;">
                        <span class="text-gray-500">Nomor Faktur: </span>
                        <span class="font-medium text-gray-900">{{ $sale->invoice_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tanggal: </span>
                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($sale->sale_date)->locale('id')->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">PO Referensi : </span>
                        <span class="font-medium text-gray-900">{{ $sale->po_number ?: '-' }}</span>
            </div>
                </div>
            </div>
        </div>



    </div>

    <div class="invoice-info">
        <table>
            <tr>
                <td class="label">Nomor Faktur:</td>
                <td><strong>{{ $sale->invoice_number }}</strong></td>
                <td class="label">Tanggal:</td>
                <td>{{ \Carbon\Carbon::parse($sale->sale_date)->locale('id')->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Nama Pelanggan:</td>
                <td>{{ $sale->customer_name }}</td>
                <td class="label">Status:</td>
                <td><strong style="text-transform: uppercase;">{{ ['pending' => 'Menunggu', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$sale->status] ?? $sale->status }}</strong></td>
            </tr>
            <tr>
                <td class="label">Telepon:</td>
                <td>{{ $sale->customer_phone ?? '-' }}</td>
                <td class="label">Staf Penjualan:</td>
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
                <td class="label">Alamat:</td>
                <td colspan="3">{{ $sale->customer_address }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Produk</th>
                <th style="width: 15%;" class="text-right">Jumlah</th>
                <th style="width: 17%;" class="text-right">Harga Satuan</th>
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
        <div class="total-row">
            Subtotal: Rp {{ number_format($sale->subtotal ?: $sale->total_amount, 0, ',', '.') }}
        </div>
        @if($sale->discount_amount > 0)
        <div class="total-row" style="color: #dc2626;">
            Diskon: - Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}
        </div>
        @endif
        @if($sale->tax_type)
        <div class="total-row" style="color: #b45309;">
            {{ strtoupper($sale->tax_type === 'ppn' ? 'PPN' : ($sale->tax_type === 'pph23' ? 'PPh 23' : $sale->tax_type)) }}
            ({{ number_format($sale->tax_rate, 0) }}%): + Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}
        </div>
        @endif
        <div class="total-row grand-total">
            TOTAL KESELURUHAN: Rp {{ number_format($sale->grand_total ?: $sale->total_amount, 0, ',', '.') }}
        </div>
    </div>

    @if($sale->notes)
    <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #4F46E5;">
        <strong>Catatan:</strong><br>
        {{ $sale->notes }}
    </div>
    @endif

    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda!</p>
        <p style="margin-top: 10px;">Dibuat pada {{ now()->locale('id')->translatedFormat('d F Y - H:i') }}</p>
    </div>
</body>
</html>
