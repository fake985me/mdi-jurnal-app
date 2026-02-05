<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .greeting {
            font-size: 18px;
            color: #334155;
            margin-bottom: 20px;
        }
        .message {
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .code-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }
        .code {
            font-size: 36px;
            font-weight: 700;
            color: #0ea5e9;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .expiry {
            color: #f59e0b;
            font-size: 14px;
            margin-top: 15px;
            font-weight: 500;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 25px 0;
            text-align: left;
            border-radius: 0 8px 8px 0;
        }
        .warning p {
            color: #92400e;
            margin: 0;
            font-size: 14px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Kode Verifikasi Login</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Halo <strong>{{ $user->name }}</strong>,</p>
            
            <p class="message">
                Anda menerima email ini karena ada permintaan login ke akun Anda. 
                Gunakan kode berikut untuk menyelesaikan proses login:
            </p>
            
            <div class="code-container">
                <div class="code">{{ $code }}</div>
                <p class="expiry">⏱️ Kode berlaku selama 10 menit</p>
            </div>
            
            <div class="warning">
                <p>
                    <strong>⚠️ Peringatan:</strong> Jika Anda tidak meminta kode ini, 
                    abaikan email ini. Seseorang mungkin mencoba mengakses akun Anda.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        </div>
    </div>
</body>
</html>
