<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\ProdukController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/admin/dashboard', DashboardController::class)->middleware('auth')->name('admin.dashboard');
Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
Route::post('/keranjang/tambah', [KeranjangController::class, 'tambah'])->name('keranjang.tambah');
Route::patch('/keranjang/{produk}', [KeranjangController::class, 'update'])->name('keranjang.update');
Route::delete('/keranjang/{produk}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');
Route::delete('/keranjang', [KeranjangController::class, 'clear'])->name('keranjang.clear');

// Hanya untuk yang belum login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Hanya untuk yang sudah login
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
