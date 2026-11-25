@extends('layouts.app')

@section('judul', 'Pembayaran ' . ($pesanan->invoice_number ?? $pesanan->kode))

@section('isi')
@php
    $unpaid     = $pesanan->status_pembayaran === 'unpaid';
    $punyaToken = $unpaid && filled($pesanan->snap_token);
    $clientKey  = config('midtrans.client_key');
    $snapUrl    = config('midtrans.is_production')
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
@endphp

<h1 class="mb-1 text-xl font-semibold">Pembayaran</h1>
<p class="mb-4 text-sm text-gray-500">Invoice <strong>{{ $pesanan->invoice_number ?? $pesanan->kode }}</strong> &middot; Diproses aman oleh <strong>Midtrans</strong>.</p>

<div class="grid gap-6 lg:grid-cols-2">

    {{-- Ringkasan pesanan --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Ringkasan Pesanan</h2>

        <dl class="mb-4 grid grid-cols-[110px_1fr] gap-y-1 text-sm">
            <dt class="text-gray-500">Kode</dt><dd>{{ $pesanan->kode }}</dd>
            <dt class="text-gray-500">Tanggal</dt><dd>{{ $pesanan->tanggal_pesan->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Status</dt><dd><x-status-badge :nilai="$pesanan->status_pembayaran" /></dd>
        </dl>

        <ul class="divide-y divide-gray-100 border-t border-gray-200 text-sm">
            @foreach ($pesanan->detail as $d)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $d->produk?->nama ?? $d->jasa?->nama }} <span class="text-gray-400">x{{ $d->qty }}</span></span>
                    <span>Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-3 space-y-1 border-t border-gray-200 pt-3 text-sm">
            @if ($tipe === 'produk')
                <div class="flex justify-between"><dt>Subtotal</dt><dd>Rp {{ number_format($pesanan->detail->sum('subtotal'), 0, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt>Ongkir</dt><dd>Rp {{ number_format($pesanan->ongkir, 0, ',', '.') }}</dd></div>
            @endif
            <div class="flex justify-between pt-1 text-base font-semibold">
                <dt>Total</dt><dd class="text-brand-700">Rp {{ number_format($pesanan->total_bayar, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </section>

    {{-- Jendela pembayaran --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Bayar via Midtrans Snap</h2>

        @if ($pesanan->status_pesanan === 'dibatalkan')
            <p class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">Pesanan ini sudah dibatalkan dan tidak dapat dibayar.</p>
        @elseif ($punyaToken)
            @if (request()->boolean('menunggu'))
                <p class="mb-3 rounded-md bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Pembayaran belum dikonfirmasi Midtrans. Status akan diperbarui otomatis, atau tekan
                    <strong>Bayar Sekarang</strong> untuk melanjutkan pembayaran.
                </p>
            @endif

            <p class="mb-3 text-sm text-gray-600">Tekan tombol di bawah untuk membuka jendela pembayaran. Semua metode (QRIS, e-wallet, transfer bank, kartu) dipilih di dalam jendela Midtrans.</p>

            <div id="bayar-area">
                <x-button id="tombol-bayar" type="button" class="w-full py-3">Bayar Sekarang</x-button>
            </div>

            <p id="pesan-tutup" class="mt-3 hidden rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Jendela pembayaran ditutup tanpa menyelesaikan pembayaran. Tekan <strong>Bayar Sekarang</strong> untuk melanjutkan dengan token yang sama.
            </p>

            <form method="POST" action="{{ route('payment.retry', ['tipe' => $tipe, 'pesanan' => $pesanan->id]) }}" class="mt-5 border-t border-gray-200 pt-4"
                  onsubmit="return confirm('Buat token pembayaran baru untuk pesanan ini?')">
                @csrf
                <p class="mb-2 text-xs text-gray-500">Transaksi gagal atau kedaluwarsa? Buat token pembayaran baru &mdash; pesanan dan invoice tetap sama, tidak ada pesanan ganda.</p>
                <x-button varian="outline" class="w-full">Coba Bayar Ulang</x-button>
            </form>
        @else
            <p class="mb-3 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Token pembayaran belum tersedia{{ session('error') ? '' : ' untuk pesanan ini' }}. Buat token pembayaran baru untuk melanjutkan.
            </p>

            <form method="POST" action="{{ route('payment.retry', ['tipe' => $tipe, 'pesanan' => $pesanan->id]) }}">
                @csrf
                <x-button class="w-full py-3">Buat Pembayaran</x-button>
            </form>
        @endif

        <p id="status-cek" class="mt-3 text-xs text-gray-500" role="status" aria-live="polite"></p>

        <p class="mt-5 border-t border-gray-200 pt-4 text-center text-xs text-gray-400">
            Sandbox Midtrans &mdash; gunakan kartu uji atau QRIS sandbox. Tidak ada uang sungguhan yang ditransfer.
        </p>
    </section>
</div>

{{-- Snap popup --}}
@if ($punyaToken && $pesanan->status_pesanan !== 'dibatalkan')
    @if (filled($clientKey))
        <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
        <script>
            (function () {
                var keSukses   = {{ Illuminate\Support\Js::from(route('payment.success', ['tipe' => $tipe, 'pesanan' => $pesanan->id])) }};
                var token      = {{ Illuminate\Support\Js::from($pesanan->snap_token) }};
                var tombol     = document.getElementById('tombol-bayar');
                var pesanTutup = document.getElementById('pesan-tutup');

                function tampilPesan(teks) {
                    if (! pesanTutup) return;
                    pesanTutup.textContent = teks;
                    pesanTutup.classList.remove('hidden');
                }

                function aktifkanTombol() {
                    if (tombol) tombol.removeAttribute('disabled');
                }

                function bukaSnap() {
                    if (pesanTutup) pesanTutup.classList.add('hidden');
                    if (tombol) tombol.setAttribute('disabled', 'disabled');

                    if (! window.snap) {
                        tampilPesan('Jendela pembayaran Midtrans gagal dimuat. Periksa koneksi lalu coba lagi.');
                        aktifkanTombol();
                        return;
                    }

                    window.snap.pay(token, {
                        onSuccess: function () { window.location.href = keSukses; },
                        onPending: function () {
                            tampilPesan('Pembayaran sedang diproses. Status di halaman ini akan diperbarui otomatis.');
                            aktifkanTombol();
                        },
                        onError: function () {
                            tampilPesan('Pembayaran gagal diproses. Tekan "Coba Bayar Ulang" untuk membuat pembayaran baru.');
                            aktifkanTombol();
                        },
                        onClose: function () {
                            tampilPesan('Jendela pembayaran ditutup. Jika Anda sudah membayar, status akan diperbarui otomatis di halaman ini.');
                            aktifkanTombol();
                        }
                    });
                }

                if (tombol) tombol.addEventListener('click', bukaSnap);

                @if (! request()->boolean('menunggu'))
                    bukaSnap();   // popup langsung terbuka saat halaman dibuka
                @endif
            })();
        </script>
    @else
        <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            MIDTRANS_CLIENT_KEY belum diisi di file <code>.env</code>, jadi jendela pembayaran tidak dapat dibuka.
        </p>
    @endif
@endif

{{-- Polling status: menangkap pembayaran VA/e-wallet yang selesai setelah popup ditutup --}}
@if ($pesanan->status_pesanan !== 'dibatalkan')
    <script>
        (function () {
            var urlStatus  = {{ Illuminate\Support\Js::from(route('payment.status', ['tipe' => $tipe, 'pesanan' => $pesanan->id])) }};
            var urlDetail  = {{ Illuminate\Support\Js::from(route($tipe === 'jasa' ? 'pesanan-jasa.show' : 'pesanan.show', $pesanan->id)) }};
            var statusAwal = {{ Illuminate\Support\Js::from($pesanan->status_pembayaran) }};
            var elStatus   = document.getElementById('status-cek');
            var mulai      = Date.now();
            var sudahMuatUlang = false;

            function tulis(teks) {
                if (elStatus) elStatus.textContent = teks;
            }

            function cekStatus() {
                // Jangan polling saat tab tidak aktif; berhenti otomatis setelah 30 menit.
                if (document.hidden || Date.now() - mulai > 30 * 60 * 1000) return;

                fetch(urlStatus, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (! data) return;

                        if (data.status === 'paid') {
                            tulis('Pembayaran diterima. Mengalihkan ke detail pesanan...');
                            window.location.href = urlDetail;
                            return;
                        }

                        if (data.status === 'dibatalkan') {
                            window.location.reload();
                            return;
                        }

                        if (data.status === 'failed') {
                            tulis('Pembayaran gagal atau kedaluwarsa. Tekan "Coba Bayar Ulang" untuk membuat pembayaran baru.');

                            // Muat ulang sekali supaya tombol bayar ulang tampil.
                            if (! sudahMuatUlang && statusAwal !== 'failed') {
                                sudahMuatUlang = true;
                                window.location.reload();
                            }
                            return;
                        }

                        tulis('Menunggu pembayaran... status diperbarui otomatis.');
                    })
                    .catch(function () { /* gangguan jaringan sesaat: dicoba lagi pada interval berikutnya */ });
            }

            setInterval(cekStatus, 7000);
            document.addEventListener('visibilitychange', function () {
                if (! document.hidden) cekStatus();
            });
            cekStatus();
        })();
    </script>
@endif
@endsection
