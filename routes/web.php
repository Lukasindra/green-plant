<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\ProdukController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/produk');

Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');

// Keranjang (session, boleh tanpa login)
Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
Route::post('/keranjang/tambah', [KeranjangController::class, 'tambah'])->name('keranjang.tambah');
Route::post('/keranjang/ubah', [KeranjangController::class, 'ubah'])->name('keranjang.ubah');
Route::delete('/keranjang/{produk}', [KeranjangController::class, 'hapus'])->name('keranjang.hapus');

// Checkout: sementara placeholder, halamannya dibuat di tahap berikutnya
Route::get('/checkout', fn () => redirect()->route('keranjang.index')->with('status', 'Halaman checkout sedang dikerjakan.'))
    ->middleware('auth')->name('checkout.index');

// Hanya untuk yang belum login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Hanya untuk yang sudah login
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
