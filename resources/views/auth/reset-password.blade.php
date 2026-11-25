@extends('layouts.auth')

@section('judul', 'Reset Password')

@section('isi')
<h2 class="text-2xl font-semibold">Reset Password</h2>
<p class="mt-1 text-sm text-gray-500">Buat password baru untuk akun Anda.</p>

<form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-3">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <x-input nama="email" label="Email" tipe="email" required value="{{ old('email', $email) }}" autocomplete="email" />
    <x-input nama="password" label="Password baru" tipe="password" required autocomplete="new-password" />
    <x-input nama="password_confirmation" label="Ulangi password baru" tipe="password" required autocomplete="new-password" />

    <x-button class="mt-2 w-full">Simpan Password Baru</x-button>
</form>

<p class="mt-6 text-center text-sm text-gray-500">
    <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Kembali ke login</a>
</p>
@endsection
