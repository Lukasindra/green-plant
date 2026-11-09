<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi rute berdasarkan peran akun. Dipakai setelah 'auth', contoh:
 *   ->middleware(['auth', 'role:admin'])
 *   ->middleware(['auth', 'role:admin,kurir'])
 * Akun dengan peran lain mendapat 403.
 */
class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $akun = $request->user();

        abort_unless($akun && in_array($akun->role, $peran, true), 403);

        return $next($request);
    }
}
