@extends('layouts.admin')

@section('judul', 'Tambah Pengguna')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('admin.pengguna.store') }}"
            class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @include('admin.pengguna.partials._form')

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                    Simpan Pengguna
                </button>
                <a href="{{ route('admin.pengguna.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
            </div>
        </form>
    </div>
@endsection
