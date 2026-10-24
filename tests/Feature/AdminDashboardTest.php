<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
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
            $table->string('alamat');
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
            $table->string('satuan')->nullable();
            $table->string('foto')->nullable();
        });

        foreach (['pesanan_produk', 'pesanan_jasa'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('kode');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->date('tanggal_pesan');
                $table->unsignedInteger('total_bayar')->default(0);
            });
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_open_the_admin_dashboard(): void
    {
        $customer = $this->createAccount('Pelanggan', 'customer');

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_sees_dashboard_totals_and_recent_orders(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $customerAccountId = DB::table('akun')->insertGetId([
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        $customerId = DB::table('customer')->insertGetId([
            'akun_id' => $customerAccountId,
            'alamat' => 'Bandung',
        ]);

        DB::table('produk')->insert([
            'nama' => 'Monstera',
            'harga' => 25000,
            'stok' => 5,
        ]);
        DB::table('jasa')->insert([
            'nama' => 'Perawatan Tanaman',
            'harga' => 50000,
        ]);
        DB::table('pesanan_produk')->insert([
            'kode' => 'PSN-P-001',
            'customer_id' => $customerId,
            'tanggal_pesan' => now()->toDateString(),
            'total_bayar' => 75000,
        ]);
        DB::table('pesanan_jasa')->insert([
            'kode' => 'PSN-J-001',
            'customer_id' => $customerId,
            'tanggal_pesan' => now()->toDateString(),
            'total_bayar' => 50000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Admin Green Plant')
            ->assertSee('Total Pesanan')
            ->assertSee('2')
            ->assertSee('Total Produk')
            ->assertSee('Total Jasa')
            ->assertSee('Rp 125.000')
            ->assertSee('PSN-P-001')
            ->assertSee('PSN-J-001')
            ->assertSee('Budi Santoso')
            ->assertSee('Nilai Pesanan');
    }

    public function test_admin_login_redirects_to_the_dashboard(): void
    {
        $this->createAccount('Admin Green Plant', 'admin', 'admin@example.test');

        $response = $this->post(route('login'), [
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    private function createAccount(string $name, string $role, string $email = 'admin@example.test'): Akun
    {
        $accountId = DB::table('akun')->insertGetId([
            'nama' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        return Akun::findOrFail($accountId);
    }
}
