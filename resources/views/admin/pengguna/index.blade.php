@extends('layouts.admin')

@section('judul', 'Pengguna')

@section('isi')
    <div class="space-y-6">
        {{-- Filter role & pencarian --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pengguna.index') }}"
                    @if (! $role) aria-current="page" @endif
                    class="rounded-full px-4 py-1.5 text-sm font-medium {{ ! $role ? 'bg-brand-700 text-white' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                    Semua
                </a>
                @foreach ($daftarRole as $peran)
                    <a href="{{ route('admin.pengguna.index', ['role' => $peran]) }}"
                        @if ($role === $peran) aria-current="page" @endif
                        class="rounded-full px-4 py-1.5 text-sm font-medium @if ($role === $peran) bg-brand-700 text-white @else border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 @endif">
                        {{ ucfirst($peran) }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.pengguna.index') }}"
                class="flex items-end gap-2" role="search" aria-label="Cari pengguna">
                @if ($role)
                    <input type="hidden" name="role" value="{{ $role }}">
                @endif
                <div>
                    <label for="q" class="mb-1 block text-sm font-medium text-gray-700">Cari</label>
                    <input type="search" id="q" name="q" value="{{ $kata }}" placeholder="Nama atau email"
                        class="w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Cari
                </button>
            </form>

            <a href="{{ route('admin.pengguna.create') }}"
                class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                + Tambah Pengguna
            </a>
        </div>

        <section aria-label="Daftar pengguna" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Nama</th>
                            <th scope="col" class="px-3 py-3 font-medium">Email</th>
                            <th scope="col" class="px-3 py-3 font-medium">No HP</th>
                            <th scope="col" class="px-3 py-3 font-medium">Role</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($pengguna as $akun)
                            @php
                                $kelasRole = match ($akun->role) {
                                    'admin' => 'bg-purple-100 text-purple-800',
                                    'kurir' => 'bg-sky-100 text-sky-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{{ $akun->nama }}</td>
                                <td class="px-3 py-3.5 text-gray-600">{{ $akun->email }}</td>
                                <td class="px-3 py-3.5 text-gray-600">{{ $akun->no_hp ?: '-' }}</td>
                                <td class="px-3 py-3.5">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kelasRole }}">
                                        {{ ucfirst($akun->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.pengguna.edit', ['pengguna' => $akun]) }}"
                                            class="font-medium text-brand-800 hover:text-brand-900">Edit</a>
                                        @if (auth()->user()->id !== $akun->id)
                                            <form method="POST" action="{{ route('admin.pengguna.destroy', ['pengguna' => $akun]) }}"
                                                onsubmit="return confirm('Hapus pengguna {{ $akun->nama }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 hover:text-red-800">Hapus</button>
                                            </form>
                                        @else
                                            <span class="text-gray-400">(Anda)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-gray-500">Tidak ada pengguna ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $pengguna->links() }}
            </div>
        </section>
    </div>
@endsection
