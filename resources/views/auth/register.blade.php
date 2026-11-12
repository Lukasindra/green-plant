@extends('layouts.auth')

@section('judul', 'Daftar')

@section('isi')
<h2 class="text-2xl font-semibold">Daftar</h2>
<p class="mt-1 text-sm text-gray-500">Buat akun pelanggan Green Plant</p>

<form method="POST" action="{{ route('daftar') }}" class="mt-6 space-y-3">
    @csrf

    <x-input nama="nama" label="Nama lengkap" required value="{{ old('nama') }}" autofocus />
    <x-input nama="email" label="Email" tipe="email" required autocomplete="email" value="{{ old('email') }}" />
    <x-input nama="no_hp" label="No. HP (opsional)" inputmode="tel" value="{{ old('no_hp') }}" />

    <x-textarea nama="alamat" label="Alamat" :baris="2" required>{{ old('alamat') }}</x-textarea>

    <div class="grid gap-3 sm:grid-cols-2">
        <x-input nama="password" label="Password" tipe="password" required autocomplete="new-password" />
        <x-input nama="password_confirmation" label="Ulangi password" tipe="password" required autocomplete="new-password" />
    </div>

    <x-button class="mt-2 w-full">Daftar</x-button>
</form>

<p class="mt-5 text-center text-sm text-gray-500">
    Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Login</a>
</p>
@endsection
