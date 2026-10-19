<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, $request->boolean('ingat'))) {
            // Pesan sengaja umum, supaya tidak membocorkan email mana yang terdaftar
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        // TODO: arahkan admin ke dashboard dan kurir ke dasbor kurir setelah halamannya dibuat
        return redirect()->intended(route('produk.index'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:100', 'unique:akun,email'],
            'no_hp'    => ['nullable', 'regex:/^[0-9+]{8,20}$/'],
            'alamat'   => ['required', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique'    => 'Email ini sudah terdaftar.',
            'password.min'    => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'no_hp.regex'     => 'Nomor HP hanya berisi angka (8 sampai 20 digit).',
        ]);

        $akun = DB::transaction(function () use ($data) {
            $akun = new Akun([
                'nama'     => $data['nama'],
                'email'    => $data['email'],
                'password' => $data['password'],   // di-hash otomatis oleh cast
                'no_hp'    => $data['no_hp'] ?? null,
            ]);
            $akun->role = 'customer';              // peran selalu customer untuk pendaftaran publik
            $akun->save();

            Customer::create(['akun_id' => $akun->id, 'alamat' => $data['alamat']]);

            return $akun;
        });

        Auth::login($akun);
        $request->session()->regenerate();

        return redirect()->route('produk.index')->with('status', 'Akun berhasil dibuat. Selamat datang, ' . $akun->nama . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda sudah keluar.');
    }
}
