<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Profil akun sendiri: nama, nomor HP, alamat (customer), dan password. */
class ProfilController extends Controller
{
    public function index(Request $request): View
    {
        $akun = $request->user();

        return view('profil.index', [
            'akun' => $akun,
            'customer' => $akun->customer,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $akun = $request->user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
        ]);

        $akun->nama = $data['nama'];
        $akun->no_hp = $data['no_hp'] ?? null;
        $akun->save();

        // Alamat hanya dimiliki akun customer.
        if ($akun->customer) {
            $akun->customer->alamat = $data['alamat'] ?? null;
            $akun->customer->save();
        }

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_lama.current_password' => 'Password lama tidak cocok.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Password berhasil diperbarui.');
    }
}
