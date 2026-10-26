<?php
use App\Http\Controllers\Admin\JasaController as AdminJasaController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\PengaturanController as AdminPengaturanController;
use App\Http\Controllers\Admin\PenggunaController as AdminPenggunaController;
use App\Http\Controllers\Admin\PembayaranController as AdminPembayaranController;
use App\Http\Controllers\Admin\PengirimanController as AdminPengirimanController;
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PesananJasaController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\JasaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/produk');

Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/jasa', [JasaController::class, 'index'])->name('jasa.index');
Route::get('/jasa/{jasa}', [JasaController::class, 'show'])->name('jasa.show');
Route::get('/admin/dashboard', DashboardController::class)->middleware('auth')->name('admin.dashboard');

// Kelola produk (admin)
Route::get('/admin/produk', [AdminProdukController::class, 'index'])->middleware('auth')->name('admin.produk.index');
Route::get('/admin/produk/tambah', [AdminProdukController::class, 'create'])->middleware('auth')->name('admin.produk.create');
Route::post('/admin/produk', [AdminProdukController::class, 'store'])->middleware('auth')->name('admin.produk.store');
Route::get('/admin/produk/{produk}/edit', [AdminProdukController::class, 'edit'])->middleware('auth')->name('admin.produk.edit');
Route::put('/admin/produk/{produk}', [AdminProdukController::class, 'update'])->middleware('auth')->name('admin.produk.update');
Route::delete('/admin/produk/{produk}', [AdminProdukController::class, 'destroy'])->middleware('auth')->name('admin.produk.destroy');

// Kelola jasa (admin)
Route::get('/admin/jasa', [AdminJasaController::class, 'index'])->middleware('auth')->name('admin.jasa.index');
Route::get('/admin/jasa/tambah', [AdminJasaController::class, 'create'])->middleware('auth')->name('admin.jasa.create');
Route::post('/admin/jasa', [AdminJasaController::class, 'store'])->middleware('auth')->name('admin.jasa.store');
Route::get('/admin/jasa/{jasa}/edit', [AdminJasaController::class, 'edit'])->middleware('auth')->name('admin.jasa.edit');
Route::put('/admin/jasa/{jasa}', [AdminJasaController::class, 'update'])->middleware('auth')->name('admin.jasa.update');
Route::delete('/admin/jasa/{jasa}', [AdminJasaController::class, 'destroy'])->middleware('auth')->name('admin.jasa.destroy');

// Kelola pesanan (admin): produk & jasa
Route::get('/admin/pesanan', [AdminPesananController::class, 'index'])->middleware('auth')->name('admin.pesanan.index');
Route::get('/admin/pesanan/bukti/{tipe}/{id}', [AdminPesananController::class, 'bukti'])->middleware('auth')->name('admin.pesanan.bukti');
Route::get('/admin/pesanan/produk/{pesanan}', [AdminPesananController::class, 'showProduk'])->middleware('auth')->name('admin.pesanan.produk.show');
Route::get('/admin/pesanan/jasa/{pesanan}', [AdminPesananController::class, 'showJasa'])->middleware('auth')->name('admin.pesanan.jasa.show');
Route::post('/admin/pesanan/produk/{pesanan}/aksi', [AdminPesananController::class, 'aksiProduk'])->middleware('auth')->name('admin.pesanan.produk.aksi');
Route::post('/admin/pesanan/jasa/{pesanan}/aksi', [AdminPesananController::class, 'aksiJasa'])->middleware('auth')->name('admin.pesanan.jasa.aksi');

// Pembayaran (admin): fokus verifikasi bukti bayar & COD
Route::get('/admin/pembayaran', [AdminPembayaranController::class, 'index'])->middleware('auth')->name('admin.pembayaran.index');

// Pengiriman (admin): penugasan kurir & pelacakan status
Route::get('/admin/pengiriman', [AdminPengirimanController::class, 'index'])->middleware('auth')->name('admin.pengiriman.index');
Route::post('/admin/pengiriman/{pesanan}/aksi', [AdminPengirimanController::class, 'aksi'])->middleware('auth')->name('admin.pengiriman.aksi');

// Laporan (admin): rekap penjualan produk & jasa per periode
Route::get('/admin/laporan', [AdminLaporanController::class, 'index'])->middleware('auth')->name('admin.laporan.index');

// Pengguna (admin): kelola akun admin/kurir/customer
Route::get('/admin/pengguna', [AdminPenggunaController::class, 'index'])->middleware('auth')->name('admin.pengguna.index');
Route::get('/admin/pengguna/tambah', [AdminPenggunaController::class, 'create'])->middleware('auth')->name('admin.pengguna.create');
Route::post('/admin/pengguna', [AdminPenggunaController::class, 'store'])->middleware('auth')->name('admin.pengguna.store');
Route::get('/admin/pengguna/{pengguna}/edit', [AdminPenggunaController::class, 'edit'])->middleware('auth')->name('admin.pengguna.edit');
Route::put('/admin/pengguna/{pengguna}', [AdminPenggunaController::class, 'update'])->middleware('auth')->name('admin.pengguna.update');
Route::delete('/admin/pengguna/{pengguna}', [AdminPenggunaController::class, 'destroy'])->middleware('auth')->name('admin.pengguna.destroy');

// Pengaturan (admin): ongkir, rekening transfer & e-wallet toko
Route::get('/admin/pengaturan', [AdminPengaturanController::class, 'index'])->middleware('auth')->name('admin.pengaturan.index');
Route::put('/admin/pengaturan', [AdminPengaturanController::class, 'simpan'])->middleware('auth')->name('admin.pengaturan.simpan');

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

// Hanya untuk yang sudah login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');

    Route::post('/jasa/{jasa}', [JasaController::class, 'pesan'])->name('jasa.pesan')->middleware('throttle:10,1');

    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{pesanan}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan/{pesanan}/bukti', [PesananController::class, 'bukti'])->name('pesanan.bukti');
    Route::post('/pesanan/{pesanan}/bukti', [PesananController::class, 'uploadBukti'])
        ->name('pesanan.bukti.simpan')->middleware('throttle:10,1');

    Route::get('/pesanan-jasa', [PesananJasaController::class, 'index'])->name('pesanan-jasa.index');
    Route::get('/pesanan-jasa/{pesanan}', [PesananJasaController::class, 'show'])->name('pesanan-jasa.show');
    Route::get('/pesanan-jasa/{pesanan}/bukti', [PesananJasaController::class, 'bukti'])->name('pesanan-jasa.bukti');
    Route::post('/pesanan-jasa/{pesanan}/bukti', [PesananJasaController::class, 'uploadBukti'])
        ->name('pesanan-jasa.bukti.simpan')->middleware('throttle:10,1');
});
