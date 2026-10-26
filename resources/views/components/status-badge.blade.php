@props(['nilai'])
@php
    $peta = [
        'menunggu_bayar'       => ['Belum dibayar',        'bg-amber-100 text-amber-800'],
        'menunggu_verifikasi'  => ['Menunggu verifikasi',  'bg-blue-100 text-blue-800'],
        'lunas'                => ['Lunas',                'bg-brand-100 text-brand-800'],
        'menunggu_pembayaran'  => ['Menunggu pembayaran',  'bg-amber-100 text-amber-800'],
        'dibayar'              => ['Diproses',             'bg-blue-100 text-blue-800'],
        'dikirim'              => ['Dikirim',              'bg-indigo-100 text-indigo-800'],
        'selesai'              => ['Selesai',              'bg-brand-100 text-brand-800'],
        'dibatalkan'           => ['Dibatalkan',           'bg-red-100 text-red-800'],
    ];
    [$label, $kelas] = $peta[$nilai] ?? [$nilai, 'bg-gray-100 text-gray-700'];
@endphp
<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kelas }}">{{ $label }}</span>
