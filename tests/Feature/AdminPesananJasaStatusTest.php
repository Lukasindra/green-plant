<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPesananJasaStatusTest extends TestCase
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

        Schema::create('karyawan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_id');
            $table->string('username');
        });

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->string('metode_pembayaran');
            $table->string('alamat_pengiriman');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->unsignedInteger('ongkir')->default(0);
            $table->string('status_pembayaran')->default('menunggu_bayar');
            $table->string('status_pesanan')->default('diproses');
            $table->string('bukti_bayar')->nullable();
        });

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_pembayaran');
            $table->string('alamat_lokasi');
            $table->timestamp('jadwal_pengerjaan')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedInteger('total_bayar')->default(0);
            $table->string('status_pembayaran')->default('menunggu_bayar');
            $table->string('status_pesanan')->default('diproses');
            $table->string('bukti_bayar')->nullable();
        });

        Schema::create('detail_pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pesanan_produk_id');
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('harga')->default(0);
            $table->unsignedInteger('subtotal')->default(0);
        });

        Schema::create('detail_pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pesanan_jasa_id');
            $table->unsignedBigInteger('jasa_id')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('harga')->default(0);
            $table->unsignedInteger('subtotal')->default(0);
        });
    }

    public function test_admin_can_schedule_a_paid_service_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Hana Lestari');
        $id = $this->buatPesananJasa($customer, 'PSN-J-100', 120000, ['status_pembayaran' => 'lunas']);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'jadwalkan', 'jadwal' => '2026-11-20T09:30'])
            ->assertSessionHas('status', 'Pesanan jasa dijadwalkan.');

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dijadwalkan']);
        $this->assertStringStartsWith(
            '2026-11-20 09:30',
            (string) DB::table('pesanan_jasa')->where('id', $id)->value('jadwal_pengerjaan')
        );
    }

    public function test_scheduling_without_a_new_date_keeps_the_customers_schedule(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Indra Wijaya');
        $id = $this->buatPesananJasa($customer, 'PSN-J-101', 90000, [
            'status_pembayaran' => 'lunas',
            'jadwal_pengerjaan' => '2026-11-18 08:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'jadwalkan'])
            ->assertSessionHas('status', 'Pesanan jasa dijadwalkan.');

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dijadwalkan']);
        $this->assertStringStartsWith(
            '2026-11-18 08:00',
            (string) DB::table('pesanan_jasa')->where('id', $id)->value('jadwal_pengerjaan')
        );
    }

    public function test_unpaid_service_order_cannot_be_scheduled_or_started(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Joko Susilo');
        $id = $this->buatPesananJasa($customer, 'PSN-J-102', 80000);

        foreach (['jadwalkan', 'mulai_kerja'] as $aksi) {
            $this->actingAs($admin)
                ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => $aksi])
                ->assertSessionHas('error', 'Aksi tidak dapat diterapkan pada status pesanan ini.');
        }

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'diproses']);
    }

    public function test_admin_can_run_a_service_order_from_schedule_to_finish(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Kartika Dewi');
        $id = $this->buatPesananJasa($customer, 'PSN-J-103', 150000, ['status_pembayaran' => 'lunas']);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'jadwalkan'])
            ->assertSessionHas('status', 'Pesanan jasa dijadwalkan.');
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dijadwalkan']);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'mulai_kerja'])
            ->assertSessionHas('status', 'Pengerjaan jasa dimulai.');
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dikerjakan']);

        $this->actingAs($admin)
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'selesai'])
            ->assertSessionHas('status', 'Pesanan ditandai selesai.');
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'selesai']);
    }

    public function test_service_order_cannot_go_back_after_work_started_or_finished(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Lutfi Hakim');
        $dikerjakan = $this->buatPesananJasa($customer, 'PSN-J-104', 70000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'dikerjakan',
        ]);
        $selesai = $this->buatPesananJasa($customer, 'PSN-J-105', 70000, [
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'selesai',
        ]);

        foreach ([[$dikerjakan, 'jadwalkan', 'dikerjakan'], [$dikerjakan, 'mulai_kerja', 'dikerjakan'], [$selesai, 'mulai_kerja', 'selesai']] as [$id, $aksi, $tetap]) {
            $this->actingAs($admin)
                ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => $aksi])
                ->assertSessionHas('error');

            $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => $tetap]);
        }
    }

    public function test_service_actions_do_not_apply_to_product_orders(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Maya Sari');
        $id = $this->buatPesananProduk($customer, 'PSN-P-100', 60000, ['status_pembayaran' => 'lunas']);

        foreach (['jadwalkan', 'mulai_kerja'] as $aksi) {
            $this->actingAs($admin)
                ->post(route('admin.pesanan.produk.aksi', $id), ['aksi' => $aksi])
                ->assertSessionHas('error');
        }

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'diproses']);
    }

    public function test_non_admin_cannot_schedule_service_orders(): void
    {
        [$customer] = $this->buatCustomer('Nanda Putra');
        $id = $this->buatPesananJasa($customer, 'PSN-J-106', 70000, ['status_pembayaran' => 'lunas']);

        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->post(route('admin.pesanan.jasa.aksi', $id), ['aksi' => 'jadwalkan'])
            ->assertForbidden();

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'diproses']);
    }

    public function test_detail_page_shows_schedule_buttons_only_for_paid_service_orders(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Oki Pratama');
        $lunas = $this->buatPesananJasa($customer, 'PSN-J-107', 70000, ['status_pembayaran' => 'lunas']);
        $belum = $this->buatPesananJasa($customer, 'PSN-J-108', 70000);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pesanan.jasa.show', $lunas))
            ->assertOk()
            ->assertSee('Jadwalkan Pengerjaan')
            ->assertSee('Mulai Dikerjakan');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pesanan.jasa.show', $belum))
            ->assertOk()
            ->assertDontSee('Jadwalkan Pengerjaan')
            ->assertDontSee('Mulai Dikerjakan');
    }

    public function test_admin_can_filter_service_orders_by_schedule_status(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        [$customer] = $this->buatCustomer('Putri Ayu');
        $this->buatPesananJasa($customer, 'PSN-J-109', 70000, ['status_pembayaran' => 'lunas', 'status_pesanan' => 'dijadwalkan']);
        $this->buatPesananJasa($customer, 'PSN-J-110', 70000, ['status_pembayaran' => 'lunas', 'status_pesanan' => 'dikerjakan']);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pesanan.index', ['status' => 'dijadwalkan']))
            ->assertOk()
            ->assertSee('PSN-J-109')
            ->assertDontSee('PSN-J-110');
    }

    private function buatCustomer(string $nama): array
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace([' ', '.'], ['.', '.'], $nama)).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        $customerId = DB::table('customer')->insertGetId(['akun_id' => $akunId, 'alamat' => 'Jl. Melati 1']);

        return [$customerId, $akunId];
    }

    private function buatPesananProduk(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        $id = DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Kenanga 2',
            'total_bayar' => $total,
            'ongkir' => 15000,
        ], $tambahan));

        DB::table('detail_pesanan_produk')->insert([
            'pesanan_produk_id' => $id,
            'produk_id' => null,
            'qty' => 2,
            'harga' => intdiv($total, 2),
            'subtotal' => $total,
        ]);

        return $id;
    }

    private function buatPesananJasa(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        $id = DB::table('pesanan_jasa')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'qris',
            'alamat_lokasi' => 'Perum Griya Asri',
            'total_bayar' => $total,
        ], $tambahan));

        DB::table('detail_pesanan_jasa')->insert([
            'pesanan_jasa_id' => $id,
            'jasa_id' => null,
            'qty' => 1,
            'harga' => $total,
            'subtotal' => $total,
        ]);

        return $id;
    }

    private function createAccount(string $name, string $role): Akun
    {
        $accountId = DB::table('akun')->insertGetId([
            'nama' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        return Akun::findOrFail($accountId);
    }
}
