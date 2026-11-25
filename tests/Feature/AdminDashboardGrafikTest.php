<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardGrafikTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('akun', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->string('role');
            $table->string('remember_token')->nullable();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_id');
            $table->string('alamat')->nullable();
        });

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->unsignedInteger('stok');
            $table->string('foto')->nullable();
        });

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->string('satuan');
            $table->string('foto')->nullable();
        });

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('kurir_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->string('metode_pembayaran')->nullable();
            $table->string('alamat_pengiriman');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->unsignedInteger('ongkir')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('diproses');
            $table->string('bukti_bayar')->nullable();
        });

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_pembayaran')->nullable();
            $table->string('alamat_lokasi');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('diproses');
            $table->string('bukti_bayar')->nullable();
        });
    }

    public function test_grafik_menampilkan_semua_pilihan_periode_dan_default_30_hari(): void
    {
        $admin = $this->createAccount('Admin Grafik', 'admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('30 hari terakhir')
            ->assertSee('7 Hari')
            ->assertSee('30 Hari')
            ->assertSee('90 Hari')
            ->assertSee('1 Tahun');
    }

    public function test_admin_dapat_memilih_rentang_grafik_selain_30_hari(): void
    {
        $admin = $this->createAccount('Admin Grafik', 'admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 7]))
            ->assertOk()
            ->assertSee('7 hari terakhir');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 90]))
            ->assertOk()
            ->assertSee('90 hari terakhir')
            ->assertSee('Nilai ditampilkan per minggu.');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 365]))
            ->assertOk()
            ->assertSee('12 bulan terakhir')
            ->assertSee('Nilai ditampilkan per bulan.');
    }

    public function test_periode_tidak_valid_kembali_ke_30_hari(): void
    {
        $admin = $this->createAccount('Admin Grafik', 'admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 'abc']))
            ->assertOk()
            ->assertSee('30 hari terakhir');
    }

    public function test_grafik_90_hari_menggabungkan_penjualan_per_minggu(): void
    {
        $admin = $this->createAccount('Admin Grafik', 'admin');

        // Lunas 60 hari lalu: masuk ke keranjang minggu ke-8.
        $this->buatPesananProduk('PSN-P-900', 50000, [
            'tanggal_pesan' => now()->subDays(60)->toDateString(),
            'status_pembayaran' => 'paid',
        ]);
        // Belum lunas: tidak boleh dihitung.
        $this->buatPesananProduk('PSN-P-901', 99000, [
            'tanggal_pesan' => now()->subDays(60)->toDateString(),
            'status_pembayaran' => 'unpaid',
        ]);

        $response = $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 90]))
            ->assertOk();

        $grafik = collect($response->viewData('salesChart'));

        $this->assertCount(13, $grafik);
        $this->assertSame(50000, (int) $grafik->sum('nilai'));
    }

    public function test_grafik_setahun_diagregasi_per_bulan_dan_mengabaikan_data_lama(): void
    {
        $admin = $this->createAccount('Admin Grafik', 'admin');

        $this->buatPesananProduk('PSN-P-910', 75000, [
            'tanggal_pesan' => now()->subMonths(3)->toDateString(),
            'status_pembayaran' => 'paid',
        ]);
        // Di luar jendela 12 bulan.
        $this->buatPesananProduk('PSN-P-911', 12000, [
            'tanggal_pesan' => now()->subMonths(13)->toDateString(),
            'status_pembayaran' => 'paid',
        ]);

        $response = $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 365]))
            ->assertOk();

        $grafik = collect($response->viewData('salesChart'));

        $this->assertCount(12, $grafik);
        $this->assertSame(75000, (int) $grafik->sum('nilai'));
    }

    public function test_grafik_7_hari_hanya_menghitung_penjualan_sepekan_terakhir(): void
    {
        $admin = $this->createAccount('Admin Grafik', 'admin');

        $this->buatPesananProduk('PSN-P-920', 20000, [
            'tanggal_pesan' => now()->toDateString(),
            'status_pembayaran' => 'paid',
        ]);
        $this->buatPesananProduk('PSN-P-921', 40000, [
            'tanggal_pesan' => now()->subDays(10)->toDateString(),
            'status_pembayaran' => 'paid',
        ]);

        $response = $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['periode' => 7]))
            ->assertOk();

        $grafik = collect($response->viewData('salesChart'));

        $this->assertCount(7, $grafik);
        $this->assertSame(20000, (int) $grafik->sum('nilai'));
    }

    private function createAccount(string $name, string $role): Akun
    {
        $accountId = DB::table('akun')->insertGetId([
            'nama' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        DB::table('customer')->insert([
            'akun_id' => $accountId,
            'alamat' => 'Jl. Melati 8',
        ]);

        return Akun::findOrFail($accountId);
    }

    private function buatPesananProduk(string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => 1,
            'tanggal_pesan' => now()->toDateString(),
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Melati 8',
            'total_bayar' => $total,
            'ongkir' => 15000,
        ], $tambahan));
    }
}
