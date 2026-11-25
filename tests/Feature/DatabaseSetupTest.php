<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jasa;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Memastikan repo baru bisa langsung `php artisan migrate --seed` tanpa
 * bergantung pada database Supabase yang dibuat manual.
 */
class DatabaseSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_all_core_tables(): void
    {
        foreach ([
            'akun', 'customer', 'karyawan', 'kategori', 'produk', 'jasa',
            'pesanan_produk', 'detail_pesanan_produk',
            'pesanan_jasa', 'detail_pesanan_jasa',
            'pengaturan', 'password_reset_tokens', 'riwayat_pesanan', 'reservasi_stok',
        ] as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), "Tabel {$tabel} tidak dibuat oleh migration.");
        }
    }

    public function test_seeder_creates_demo_accounts_and_catalog(): void
    {
        $this->seed();

        $this->assertDatabaseHas('akun', ['email' => 'admin@greenplant.test', 'role' => 'admin']);
        $this->assertDatabaseHas('akun', ['email' => 'kurir@greenplant.test', 'role' => 'kurir']);
        $this->assertDatabaseHas('akun', ['email' => 'customer@greenplant.test', 'role' => 'customer']);

        $this->assertGreaterThan(0, Produk::query()->count());
        $this->assertGreaterThan(0, Jasa::query()->count());

        // Password default bisa dipakai untuk login pertama.
        $admin = Akun::query()->where('email', 'admin@greenplant.test')->firstOrFail();
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
