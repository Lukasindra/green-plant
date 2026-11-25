<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Lupa password memakai password broker bawaan Laravel.
 * Provider di config/auth.php sudah memakai App\Models\Akun.
 */
class LupaPasswordController extends Controller
{
    public function form(): View
    {
        return view('auth.lupa-password');
    }

    public function kirim(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email reset gagal dikirim. Periksa konfigurasi email server lalu coba lagi.']);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Tautan reset password sudah dikirim. Periksa kotak masuk email Anda.');
        }

        $pesan = $status === Password::RESET_THROTTLED
            ? 'Tunggu sebentar sebelum meminta tautan reset lagi.'
            : 'Email tidak terdaftar pada akun Green Plant.';

        return back()->withInput($request->only('email'))->withErrors(['email' => $pesan]);
    }

    public function formReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($akun, string $password): void {
                $akun->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Password berhasil diubah. Silakan login dengan password baru.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
    }
}
