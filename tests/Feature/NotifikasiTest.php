<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Support\Notifikasi;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotifikasiTest extends TestCase
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
        });

        Schema::create('karyawan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('akun_id');
            $table->string('username');
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
            $table->string('metode_pembayaran');
            $table->string('alamat_pengiriman');
            $table->unsignedInteger('total_bayar')->default(0);
            $table->unsignedInteger('ongkir')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
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

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('stok')->default(0);
        });

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('menunggu_konfirmasi');
        });
    }

    public function test_admin_notifications_count_work_waiting_to_be_handled(): void
    {
        [$customer] = $this->buatCustomer('Bayu Aji');

        $this->buatPesananProduk($customer, 'PSN-N-001', 50000, ['status_pembayaran' => 'failed']);
        $this->buatPesananProduk($customer, 'PSN-N-002', 50000, ['status_pembayaran' => 'failed']);
        $this->buatPesananProduk($customer, 'PSN-N-003', 50000, ['status_pembayaran' => 'paid', 'status_pesanan' => 'dibayar']);
        $this->buatPesananProduk($customer, 'PSN-N-004', 50000, ['status_pembayaran' => 'paid', 'status_pesanan' => 'dikirim']);

        DB::table('pesanan_jasa')->insert([
            ['status_pembayaran' => 'failed', 'status_pesanan' => 'menunggu_pembayaran'],
            ['status_pembayaran' => 'paid', 'status_pesanan' => 'menunggu_pembayaran'],
            ['status_pembayaran' => 'paid', 'status_pesanan' => 'dijadwalkan'],
        ]);
        DB::table('produk')->insert([
            ['nama' => 'Pot Kecil', 'stok' => 2],
            ['nama' => 'Pot Besar', 'stok' => 50],
        ]);

        $hasil = Notifikasi::untuk($this->createAccount('Admin Green Plant', 'admin'));

        $this->assertSame(6, $hasil['total']);
        $this->assertSame([
            'Pembayaran gagal' => 3,
            'Pesanan siap dikirim' => 1,
            'Jasa perlu dijadwalkan' => 1,
            'Produk stok menipis' => 1,
        ], array_column($hasil['item'], 'jumlah', 'judul'));
    }

    public function test_admin_without_pending_work_has_no_notifications(): void
    {
        $hasil = Notifikasi::untuk($this->createAccount('Admin Green Plant', 'admin'));

        $this->assertSame(0, $hasil['total']);
        $this->assertSame([], $hasil['item']);
    }

    public function test_courier_notification_counts_only_own_shipments(): void
    {
        [$customer] = $this->buatCustomer('Citra Ayu');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $dewi = $this->buatKurir('Dewi', 'Dewi Kurir');

        $this->buatPesananProduk($customer, 'PSN-N-010', 40000, ['status_pesanan' => 'dikirim', 'kurir_id' => $rizal]);
        $this->buatPesananProduk($customer, 'PSN-N-011', 40000, ['status_pesanan' => 'dikirim', 'kurir_id' => $rizal]);
        $this->buatPesananProduk($customer, 'PSN-N-012', 40000, ['status_pesanan' => 'selesai', 'kurir_id' => $rizal]);
        $this->buatPesananProduk($customer, 'PSN-N-013', 40000, ['status_pesanan' => 'dikirim', 'kurir_id' => $dewi]);

        $hasil = Notifikasi::untuk($this->akunKurir($rizal));

        $this->assertSame(2, $hasil['total']);
        $this->assertSame(route('kurir.index'), $hasil['item'][0]['url']);
    }

    public function test_customers_and_guests_get_no_notifications(): void
    {
        $this->assertSame(['total' => 0, 'item' => []], Notifikasi::untuk($this->createAccount('Pelanggan', 'customer')));
        $this->assertSame(['total' => 0, 'item' => []], Notifikasi::untuk(null));
    }

    public function test_a_missing_table_does_not_break_the_other_notifications(): void
    {
        [$customer] = $this->buatCustomer('Eka Putri');
        $this->buatPesananProduk($customer, 'PSN-N-020', 50000, ['status_pembayaran' => 'failed']);

        Schema::drop('pesanan_jasa');
        Schema::drop('produk');

        $hasil = Notifikasi::untuk($this->createAccount('Admin Green Plant', 'admin'));

        $this->assertSame(1, $hasil['total']);
    }

    public function test_the_bell_is_shown_in_the_admin_layout_with_the_count(): void
    {
        [$customer] = $this->buatCustomer('Fajar Nur');
        $this->buatPesananProduk($customer, 'PSN-N-030', 50000, ['status_pembayaran' => 'failed']);

        $this->withoutVite()
            ->actingAs($this->createAccount('Admin Green Plant', 'admin'))
            ->get(route('admin.pengiriman.index'))
            ->assertOk()
            ->assertSee('Pembayaran gagal')
            ->assertSee('1 menunggu');
    }

    public function test_the_bell_says_so_when_nothing_is_waiting(): void
    {
        $this->withoutVite()
            ->actingAs($this->createAccount('Admin Green Plant', 'admin'))
            ->get(route('admin.pengiriman.index'))
            ->assertOk()
            ->assertSee('Tidak ada yang perlu ditindaklanjuti.');
    }

    public function test_the_bell_is_shown_to_couriers_but_not_customers(): void
    {
        [$customer] = $this->buatCustomer('Gita Sari');
        $rizal = $this->buatKurir('Rizal', 'Rizal Kurir');
        $this->buatPesananProduk($customer, 'PSN-N-040', 40000, ['status_pesanan' => 'dikirim', 'kurir_id' => $rizal]);

        $this->withoutVite()
            ->actingAs($this->akunKurir($rizal))
            ->get(route('kurir.index'))
            ->assertOk()
            ->assertSee('Ditugaskan admin kepada Anda');

        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->blade('<x-lonceng />')
            ->assertDontSee('data-lonceng', false);
    }

    private function akunKurir(int $karyawanId): Akun
    {
        return Akun::findOrFail(DB::table('karyawan')->where('id', $karyawanId)->value('akun_id'));
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

    /**
     * @return array<int, int>
     */
    private function buatCustomer(string $nama): array
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'.c@example.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        $customerId = DB::table('customer')->insertGetId(['akun_id' => $akunId]);

        return [$customerId, $akunId];
    }

    private function buatKurir(string $username, string $nama): int
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $username)).'.k@example.test',
            'password' => Hash::make('password'),
            'role' => 'kurir',
        ]);

        return DB::table('karyawan')->insertGetId(['akun_id' => $akunId, 'username' => $username]);
    }

    private function buatPesananProduk(int $customerId, string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => $customerId,
            'tanggal_pesan' => '2026-10-26',
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Melati 8',
            'total_bayar' => $total,
            'ongkir' => 15000,
        ], $tambahan));
    }
}
