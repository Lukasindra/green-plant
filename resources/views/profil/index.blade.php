@extends('layouts.app')

@section('judul', 'Profil Saya')

@section('isi')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold">Profil Saya</h1>
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">{{ $akun->role }}</span>
    </div>

    {{-- Data akun --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-1 text-sm font-semibold">Data Akun</h2>
        <p class="mb-4 text-xs text-gray-500">Email tidak dapat diubah. Hubungi admin bila perlu mengganti email.</p>

        <form method="POST" action="{{ route('profil.update') }}" class="space-y-3">
            @csrf
            @method('PUT')

            <x-input nama="nama" label="Nama lengkap" required value="{{ old('nama', $akun->nama) }}" />
            <x-input nama="no_hp" label="No. HP" inputmode="tel" value="{{ old('no_hp', $akun->no_hp) }}" />
            <x-input nama="email" label="Email" tipe="email" value="{{ $akun->email }}" disabled />

            @if ($customer)
                <x-textarea nama="alamat" label="Alamat pengiriman" :baris="3">{{ old('alamat', $customer->alamat) }}</x-textarea>
            @endif

            <x-button>Simpan Profil</x-button>
        </form>
    </section>

    {{-- Password --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-1 text-sm font-semibold">Ganti Password</h2>
        <p class="mb-4 text-xs text-gray-500">Gunakan minimal 8 karakter.</p>

        <form method="POST" action="{{ route('profil.password') }}" class="space-y-3">
            @csrf
            @method('PUT')

            <x-input nama="password_lama" label="Password lama" tipe="password" required autocomplete="current-password" />
            <x-input nama="password" label="Password baru" tipe="password" required autocomplete="new-password" />
            <x-input nama="password_confirmation" label="Ulangi password baru" tipe="password" required autocomplete="new-password" />

            <x-button varian="brand-outline">Perbarui Password</x-button>
        </form>
    </section>
</div>
@endsection
