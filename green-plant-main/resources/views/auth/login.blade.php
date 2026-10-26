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

    <div>
        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium">Password</label>
        <div class="relative">
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-16 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            <button type="button" onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password'; this.textContent = i.type === 'password' ? 'Lihat' : 'Sembunyi'"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500 hover:text-brand-700">Lihat</button>
        </div>
        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" name="ingat" value="1" class="rounded border-gray-300 accent-brand-700"> Ingat saya
    </label>

    <button type="submit" class="w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Login</button>
</form>

<p class="mt-6 text-center text-sm text-gray-500">
    Belum punya akun? <a href="{{ route('daftar') }}" class="font-semibold text-brand-700 hover:underline">Daftar di sini</a>
</p>
@endsection
