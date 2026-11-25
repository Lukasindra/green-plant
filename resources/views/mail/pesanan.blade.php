<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $pesanan->kode }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:24px;">
        <p style="margin:0 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#15803d;">Green Plant</p>
        <h1 style="margin:0 0 16px;font-size:20px;">
            @switch($jenis)
                @case('dibuat') Pesanan Anda sudah dibuat @break
                @case('lunas') Pembayaran diterima @break
                @case('dikirim') Pesanan sedang dikirim @break
                @case('dibatalkan') Pesanan dibatalkan @break
                @default Informasi pesanan @break
            @endswitch
        </h1>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Halo {{ $pesanan->customer?->akun?->nama ?? 'Pelanggan' }}, berikut ringkasan pesanan
            <strong>{{ $pesanan->kode }}</strong>.
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr>
                <td style="padding:6px 0;color:#6b7280;">Invoice</td>
                <td style="padding:6px 0;text-align:right;">{{ $pesanan->invoice_number ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#6b7280;">Tanggal</td>
                <td style="padding:6px 0;text-align:right;">{{ optional($pesanan->tanggal_pesan)->format('d M Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#6b7280;">Status pembayaran</td>
                <td style="padding:6px 0;text-align:right;">{{ $pesanan->status_pembayaran }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#6b7280;">Total</td>
                <td style="padding:6px 0;text-align:right;font-weight:bold;">Rp {{ number_format((int) $pesanan->total_bayar, 0, ',', '.') }}</td>
            </tr>
        </table>

        <h2 style="margin:20px 0 8px;font-size:14px;">Rincian</h2>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            @foreach ($pesanan->detail as $d)
                <tr>
                    <td style="padding:6px 0;border-top:1px solid #e5e7eb;">
                        {{ $tipe === 'jasa' ? ($d->jasa?->nama ?? 'Jasa') : ($d->produk?->nama ?? 'Produk') }}
                        <span style="color:#9ca3af;">x{{ $d->qty }}</span>
                    </td>
                    <td style="padding:6px 0;border-top:1px solid #e5e7eb;text-align:right;">
                        Rp {{ number_format((int) $d->subtotal, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </table>

        @if ($jenis === 'dibuat')
            <p style="margin:20px 0 0;font-size:14px;line-height:1.6;">
                Selesaikan pembayaran melalui halaman pesanan agar pesanan segera diproses.
            </p>
        @elseif ($jenis === 'lunas')
            <p style="margin:20px 0 0;font-size:14px;line-height:1.6;">
                Pembayaran sudah dikonfirmasi Midtrans. Kami akan segera memproses pesanan Anda.
            </p>
        @elseif ($jenis === 'dikirim')
            <p style="margin:20px 0 0;font-size:14px;line-height:1.6;">
                Pesanan sedang dalam perjalanan. Terima kasih sudah berbelanja di Green Plant.
            </p>
        @elseif ($jenis === 'dibatalkan')
            <p style="margin:20px 0 0;font-size:14px;line-height:1.6;">
                Pesanan ini dibatalkan. Bila Anda sudah terlanjur membayar, hubungi admin untuk penanganan lebih lanjut.
            </p>
        @endif

        <p style="margin:24px 0 0;font-size:12px;color:#9ca3af;">
            Email otomatis dari Green Plant. Tidak perlu membalas pesan ini.
        </p>
    </div>
</body>
</html>
