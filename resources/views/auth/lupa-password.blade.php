@extends('layouts.auth')

@section('judul', 'Lupa Password')

@section('isi')
<h2 class="text-2xl font-semibold">Lupa Password</h2>
<p class="mt-1 text-sm text-gray-500">Masukkan email akun Anda untuk menerima tautan reset.</p>

@if (session('status'))
    <div class="mt-4 rounded-md border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-brand-800">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <x-input nama="email" label="Email" tipe="email" required value="{{ old('email') }}" autofocus autocomplete="email" />
    <x-button class="w-full">Kirim Tautan Reset</x-button>
</form>

<p class="mt-6 text-center text-sm text-gray-500">
    Ingat password? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Kembali ke login</a>
</p>
@endsection
