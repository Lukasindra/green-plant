@extends('layouts.auth')

@section('judul', 'Daftar')

@section('isi')
<h2 class="text-2xl font-semibold">Daftar</h2>
<p class="mt-1 text-sm text-gray-500">Buat akun pelanggan Green Plant</p>

<form method="POST" action="{{ route('daftar') }}" class="mt-6 space-y-3">
    @csrf

    @php
        $kelas = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600';
    @endphp

    <div>
        <label for="nama" class="mb-1 block text-sm font-medium">Nama lengkap</label>
        <input id="nama" name="nama" value="{{ old('nama') }}" required autofocus class="{{ $kelas }}">
        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="{{ $kelas }}">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="no_hp" class="mb-1 block text-sm font-medium">No. HP <span class="text-gray-400">(opsional)</span></label>
        <input id="no_hp" name="no_hp" value="{{ old('no_hp') }}" inputmode="tel" class="{{ $kelas }}">
        @error('no_hp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="alamat" class="mb-1 block text-sm font-medium">Alamat</label>
        <textarea id="alamat" name="alamat" rows="2" required class="{{ $kelas }}">{{ old('alamat') }}</textarea>
        @error('alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="{{ $kelas }}">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $kelas }}">
        </div>
    </div>

    <button type="submit" class="mt-2 w-full rounded-lg bg-brand-700 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Daftar</button>
</form>

<p class="mt-5 text-center text-sm text-gray-500">
    Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Login</a>
</p>
@endsection
