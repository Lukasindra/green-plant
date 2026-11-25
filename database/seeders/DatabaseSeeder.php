<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\Customer;
use App\Models\Jasa;
use App\Models\Karyawan;
use App\Models\Kategori;
use App\Models\Pengaturan;
use App\Models\Produk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal yang aman dijalankan berulang (updateOrCreate).
 *
 * Akun demo memakai password dari env, default "password". GANTI sebelum
 * dipakai di server produksi.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make((string) env('SEED_PASSWORD', 'password'));

        $admin = Akun::updateOrCreate(
            ['email' => (string) env('SEED_ADMIN_EMAIL', 'admin@greenplant.test')],
            ['nama' => 'Admin Green Plant', 'password' => $password, 'no_hp' => '081200000001', 'role' => 'admin']
        );
        Karyawan::updateOrCreate(['akun_id' => $admin->id], ['username' => 'admin']);

        $kurir = Akun::updateOrCreate(
            ['email' => (string) env('SEED_KURIR_EMAIL', 'kurir@greenplant.test')],
            ['nama' => 'Kurir Green Plant', 'password' => $password, 'no_hp' => '081200000002', 'role' => 'kurir']
        );
        Karyawan::updateOrCreate(['akun_id' => $kurir->id], ['username' => 'kurir']);

        $customerAkun = Akun::updateOrCreate(
            ['email' => (string) env('SEED_CUSTOMER_EMAIL', 'customer@greenplant.test')],
            ['nama' => 'Pelanggan Contoh', 'password' => $password, 'no_hp' => '081200000003', 'role' => 'customer']
        );
        Customer::updateOrCreate(
            ['akun_id' => $customerAkun->id],
            ['alamat' => 'Jl. Contoh No. 1, Jakarta Selatan']
        );

        $kategoriProduk = collect(['Tanaman Hias', 'Pot & Media Tanam', 'Pupuk & Perawatan'])
            ->mapWithKeys(fn (string $nama) => [
                $nama => Kategori::updateOrCreate(['nama' => $nama, 'tipe' => 'produk'])->id,
            ]);

        $kategoriJasa = collect(['Perawatan Taman', 'Desain & Instalasi'])
            ->mapWithKeys(fn (string $nama) => [
                $nama => Kategori::updateOrCreate(['nama' => $nama, 'tipe' => 'jasa'])->id,
            ]);

        Produk::updateOrCreate(['nama' => 'Monstera Deliciosa'], [
            'kategori_id' => $kategoriProduk['Tanaman Hias'],
            'deskripsi' => 'Tanaman hias daun lebar yang cocok untuk ruangan terang.',
            'harga' => 185000,
            'stok' => 12,
        ]);
        Produk::updateOrCreate(['nama' => 'Pot Keramik Putih'], [
            'kategori_id' => $kategoriProduk['Pot & Media Tanam'],
            'deskripsi' => 'Pot keramik minimalis diameter 20 cm.',
            'harga' => 75000,
            'stok' => 25,
        ]);
        Produk::updateOrCreate(['nama' => 'Pupuk Organik 1 kg'], [
            'kategori_id' => $kategoriProduk['Pupuk & Perawatan'],
            'deskripsi' => 'Pupuk organik siap pakai untuk tanaman hias dan taman.',
            'harga' => 35000,
            'stok' => 40,
        ]);

        Jasa::updateOrCreate(['nama' => 'Perawatan Taman Bulanan'], [
            'kategori_id' => $kategoriJasa['Perawatan Taman'],
            'deskripsi' => 'Pemangkasan, penyiraman, dan pemupukan rutin satu bulan.',
            'harga' => 450000,
            'satuan' => 'kunjungan',
        ]);
        Jasa::updateOrCreate(['nama' => 'Desain Taman Minimalis'], [
            'kategori_id' => $kategoriJasa['Desain & Instalasi'],
            'deskripsi' => 'Konsep dan instalasi taman minimalis untuk rumah atau kantor.',
            'harga' => 2500000,
            'satuan' => 'paket',
        ]);

        Pengaturan::updateOrCreate(
            ['kunci' => 'ongkir'],
            ['nilai' => json_encode(15000)]
        );
    }
}
