@extends('layouts.auth')

@section('judul', 'Login')

@section('isi')
<h2 class="text-2xl font-semibold">Login</h2>
<p class="mt-1 text-sm text-gray-500">Masuk untuk melanjutkan</p>

@if (session('status'))
    <div class="mt-4 rounded-md border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-brand-800">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf

    <x-input nama="email" label="Email" tipe="email" required value="{{ old('email') }}" autofocus autocomplete="email" />

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
        <div class="relative">
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            <button type="button" onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password'; this.textContent = i.type === 'password' ? 'Lihat' : 'Sembunyi'"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500 hover:text-brand-700">Lihat</button>
        </div>
        @error('password') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" name="ingat" value="1" class="rounded border-gray-300 accent-brand-700"> Ingat saya
    </label>

    <x-button class="w-full">Login</x-button>
</form>

<p class="mt-6 text-center text-sm text-gray-500">
    Belum punya akun? <a href="{{ route('daftar') }}" class="font-semibold text-brand-700 hover:underline">Daftar di sini</a>
</p>
@endsection
