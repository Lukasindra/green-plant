<?php

use App\Http\Controllers\Admin\JasaController as AdminJasaController;
use App\Http\Controllers\Admin\KategoriController as AdminKategoriController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\PembayaranController as AdminPembayaranController;
use App\Http\Controllers\Admin\PengaturanController as AdminPengaturanController;
use App\Http\Controllers\Admin\PenggunaController as AdminPenggunaController;
use App\Http\Controllers\Admin\PengirimanController as AdminPengirimanController;
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JasaController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\Kurir\PengirimanController as KurirPengirimanController;
use App\Http\Controllers\LupaPasswordController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PesananJasaController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', BerandaController::class)->name('beranda');

Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/produk/{produk}', [ProdukController::class, 'show'])->name('produk.show');
Route::get('/jasa', [JasaController::class, 'index'])->name('jasa.index');
Route::get('/jasa/{jasa}', [JasaController::class, 'show'])->name('jasa.show');
Route::view('/tentang', 'tentang.index')->name('tentang');

// Webhook Midtrans (tanpa login, CSRF dikecualikan di bootstrap/app.php).
Route::post('/midtrans/notifikasi', MidtransNotificationController::class)
    ->name('midtrans.notifikasi')
    ->middleware('throttle:120,1');
Route::get('/admin/dashboard', DashboardController::class)->middleware(['auth', 'role:admin'])->name('admin.dashboard');

// Kelola produk (admin)
Route::get('/admin/produk', [AdminProdukController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.produk.index');
Route::get('/admin/produk/tambah', [AdminProdukController::class, 'create'])->middleware(['auth', 'role:admin'])->name('admin.produk.create');
Route::post('/admin/produk', [AdminProdukController::class, 'store'])->middleware(['auth', 'role:admin'])->name('admin.produk.store');
Route::get('/admin/produk/{produk}/edit', [AdminProdukController::class, 'edit'])->middleware(['auth', 'role:admin'])->name('admin.produk.edit');
Route::put('/admin/produk/{produk}', [AdminProdukController::class, 'update'])->middleware(['auth', 'role:admin'])->name('admin.produk.update');
Route::delete('/admin/produk/{produk}', [AdminProdukController::class, 'destroy'])->middleware(['auth', 'role:admin'])->name('admin.produk.destroy');

// Kelola jasa (admin)
Route::get('/admin/jasa', [AdminJasaController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.jasa.index');
Route::get('/admin/jasa/tambah', [AdminJasaController::class, 'create'])->middleware(['auth', 'role:admin'])->name('admin.jasa.create');
Route::post('/admin/jasa', [AdminJasaController::class, 'store'])->middleware(['auth', 'role:admin'])->name('admin.jasa.store');
Route::get('/admin/jasa/{jasa}/edit', [AdminJasaController::class, 'edit'])->middleware(['auth', 'role:admin'])->name('admin.jasa.edit');
Route::put('/admin/jasa/{jasa}', [AdminJasaController::class, 'update'])->middleware(['auth', 'role:admin'])->name('admin.jasa.update');
Route::delete('/admin/jasa/{jasa}', [AdminJasaController::class, 'destroy'])->middleware(['auth', 'role:admin'])->name('admin.jasa.destroy');

// Admin: kelola kategori produk dan jasa
Route::get('/admin/kategori', [AdminKategoriController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.kategori.index');
Route::get('/admin/kategori/tambah', [AdminKategoriController::class, 'create'])->middleware(['auth', 'role:admin'])->name('admin.kategori.create');
Route::post('/admin/kategori', [AdminKategoriController::class, 'store'])->middleware(['auth', 'role:admin'])->name('admin.kategori.store');
Route::get('/admin/kategori/{kategori}/edit', [AdminKategoriController::class, 'edit'])->middleware(['auth', 'role:admin'])->name('admin.kategori.edit');
Route::put('/admin/kategori/{kategori}', [AdminKategoriController::class, 'update'])->middleware(['auth', 'role:admin'])->name('admin.kategori.update');
Route::delete('/admin/kategori/{kategori}', [AdminKategoriController::class, 'destroy'])->middleware(['auth', 'role:admin'])->name('admin.kategori.destroy');

// Kelola pesanan (admin): produk & jasa — pembayaran diverifikasi Midtrans, admin fokus pengiriman/pengerjaan
Route::get('/admin/pesanan', [AdminPesananController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.index');
Route::get('/admin/pesanan/produk/{pesanan}', [AdminPesananController::class, 'showProduk'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.produk.show');
Route::get('/admin/pesanan/jasa/{pesanan}', [AdminPesananController::class, 'showJasa'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.jasa.show');
Route::get('/admin/pesanan/produk/{pesanan}/invoice', [AdminPesananController::class, 'invoiceProduk'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.produk.invoice');
Route::get('/admin/pesanan/jasa/{pesanan}/invoice', [AdminPesananController::class, 'invoiceJasa'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.jasa.invoice');
Route::post('/admin/pesanan/produk/{pesanan}/aksi', [AdminPesananController::class, 'aksiProduk'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.produk.aksi');
Route::post('/admin/pesanan/jasa/{pesanan}/aksi', [AdminPesananController::class, 'aksiJasa'])->middleware(['auth', 'role:admin'])->name('admin.pesanan.jasa.aksi');

// Pembayaran (admin): fokus verifikasi bukti bayar & COD
Route::get('/admin/pembayaran', [AdminPembayaranController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.pembayaran.index');

// Pengiriman (admin): penugasan kurir & pelacakan status
Route::get('/admin/pengiriman', [AdminPengirimanController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.pengiriman.index');
Route::post('/admin/pengiriman/{pesanan}/aksi', [AdminPengirimanController::class, 'aksi'])->middleware(['auth', 'role:admin'])->name('admin.pengiriman.aksi');

// Laporan (admin): rekap penjualan produk & jasa per periode
Route::get('/admin/laporan', [AdminLaporanController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.laporan.index');

// Pengguna (admin): kelola akun admin/kurir/customer
Route::get('/admin/pengguna', [AdminPenggunaController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.pengguna.index');
Route::get('/admin/pengguna/tambah', [AdminPenggunaController::class, 'create'])->middleware(['auth', 'role:admin'])->name('admin.pengguna.create');
Route::post('/admin/pengguna', [AdminPenggunaController::class, 'store'])->middleware(['auth', 'role:admin'])->name('admin.pengguna.store');
Route::get('/admin/pengguna/{pengguna}/edit', [AdminPenggunaController::class, 'edit'])->middleware(['auth', 'role:admin'])->name('admin.pengguna.edit');
Route::put('/admin/pengguna/{pengguna}', [AdminPenggunaController::class, 'update'])->middleware(['auth', 'role:admin'])->name('admin.pengguna.update');
Route::delete('/admin/pengguna/{pengguna}', [AdminPenggunaController::class, 'destroy'])->middleware(['auth', 'role:admin'])->name('admin.pengguna.destroy');

// Pengaturan (admin): ongkir, rekening transfer & e-wallet toko
Route::get('/admin/pengaturan', [AdminPengaturanController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.pengaturan.index');
Route::put('/admin/pengaturan', [AdminPengaturanController::class, 'simpan'])->middleware(['auth', 'role:admin'])->name('admin.pengaturan.simpan');

// Kurir: daftar pengantaran milik sendiri dan tandai selesai
Route::middleware(['auth', 'role:kurir'])->prefix('kurir')->name('kurir.')->group(function () {
    Route::get('/', [KurirPengirimanController::class, 'index'])->name('index');
    Route::get('/pesanan/{pesanan}', [KurirPengirimanController::class, 'show'])->name('show');
    Route::post('/pesanan/{pesanan}/selesai', [KurirPengirimanController::class, 'selesai'])->name('selesai');
});

// Keranjang (session, boleh tanpa login)
Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
Route::post('/keranjang/tambah', [KeranjangController::class, 'tambah'])->name('keranjang.tambah');
Route::post('/keranjang/ubah', [KeranjangController::class, 'ubah'])->name('keranjang.ubah');
Route::delete('/keranjang/{produk}', [KeranjangController::class, 'hapus'])->name('keranjang.hapus');

// Hanya untuk yang belum login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Lupa/reset password (boleh diakses walau sudah login, mis. dari email)
Route::get('/lupa-password', [LupaPasswordController::class, 'form'])->name('password.request');
Route::post('/lupa-password', [LupaPasswordController::class, 'kirim'])
    ->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password/{token}', [LupaPasswordController::class, 'formReset'])->name('password.reset');
Route::post('/reset-password', [LupaPasswordController::class, 'reset'])
    ->middleware('throttle:6,1')->name('password.update');

// Hanya untuk yang sudah login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');

    Route::post('/jasa/{jasa}', [JasaController::class, 'pesan'])->name('jasa.pesan')->middleware('throttle:10,1');

    // Midtrans Snap: popup pembayaran, callback sukses, dan bayar ulang.
    // {tipe} = produk|jasa. {pesanan} = id record pesanan.
    Route::get('/pembayaran/{tipe}/{pesanan}', [PaymentController::class, 'show'])->name('payment.show');
    Route::get('/pembayaran/{tipe}/{pesanan}/sukses', [PaymentController::class, 'success'])
        ->name('payment.success');
    Route::get('/pembayaran/{tipe}/{pesanan}/status', [PaymentController::class, 'status'])
        ->name('payment.status')->middleware('throttle:120,1');
    Route::post('/pembayaran/{tipe}/{pesanan}/bayar-ulang', [PaymentController::class, 'retry'])
        ->name('payment.retry')->middleware('throttle:10,1');

    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{pesanan}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan/{pesanan}/invoice', [PesananController::class, 'invoice'])->name('pesanan.invoice');
    Route::post('/pesanan/{pesanan}/batalkan', [PesananController::class, 'batalkan'])
        ->name('pesanan.batalkan')->middleware('throttle:10,1');

    Route::get('/pesanan-jasa', [PesananJasaController::class, 'index'])->name('pesanan-jasa.index');
    Route::get('/pesanan-jasa/{pesanan}', [PesananJasaController::class, 'show'])->name('pesanan-jasa.show');
    Route::get('/pesanan-jasa/{pesanan}/invoice', [PesananJasaController::class, 'invoice'])->name('pesanan-jasa.invoice');
    Route::post('/pesanan-jasa/{pesanan}/batalkan', [PesananJasaController::class, 'batalkan'])
        ->name('pesanan-jasa.batalkan')->middleware('throttle:10,1');
});
