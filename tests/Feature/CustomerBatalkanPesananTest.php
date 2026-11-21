<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerBatalkanPesananTest extends TestCase
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

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('stok')->default(0);
        });

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });
    }

    public function test_owner_can_cancel_an_unpaid_product_order(): void
    {
        [$customer, $akun] = $this->buatCustomer('Alya Safitri');
        $produk = DB::table('produk')->insertGetId(['nama' => 'Pot Tanah', 'stok' => 9]);
        $id = $this->buatPesananProduk($customer, 'PSN-C-001', 60000);
        DB::table('detail_pesanan_produk')->where('pesanan_produk_id', $id)->update(['produk_id' => $produk]);

        $this->actingAs($this->akun($akun))
            ->post(route('pesanan.batalkan', $id))
            ->assertRedirect(route('pesanan.show', $id))
            ->assertSessionHas('status', 'Pesanan PSN-C-001 berhasil dibatalkan.');

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'dibatalkan']);
        // Stok belum pernah dikurangi untuk pesanan yang belum lunas, jadi tidak berubah
        $this->assertSame(9, (int) DB::table('produk')->where('id', $produk)->value('stok'));
    }

    public function test_owner_can_cancel_an_unpaid_service_order(): void
    {
        [$customer, $akun] = $this->buatCustomer('Bagas Pratama');
        $id = $this->buatPesananJasa($customer, 'PSN-C-002', 90000);

        $this->actingAs($this->akun($akun))
            ->post(route('pesanan-jasa.batalkan', $id))
            ->assertRedirect(route('pesanan-jasa.show', $id))
            ->assertSessionHas('status', 'Pesanan PSN-C-002 berhasil dibatalkan.');

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dibatalkan']);
    }

    public function test_an_unpaid_cod_order_can_be_cancelled(): void
    {
        [$customer, $akun] = $this->buatCustomer('Citra Lestari');
        $id = $this->buatPesananProduk($customer, 'PSN-C-003', 60000, ['metode_pembayaran' => 'cod']);

        $this->actingAs($this->akun($akun))
            ->post(route('pesanan.batalkan', $id))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pesanan_produk', ['id' => $id, 'status_pesanan' => 'dibatalkan']);
    }

    public function test_an_order_cannot_be_cancelled_once_proof_is_uploaded_or_it_is_paid_or_moving(): void
    {
        [$customer, $akun] = $this->buatCustomer('Dimas Anggara');
        $pemilik = $this->akun($akun);

        $kasus = [
            ['status_pembayaran' => 'menunggu_verifikasi'],
            ['status_pembayaran' => 'lunas'],
            ['status_pembayaran' => 'lunas', 'status_pesanan' => 'dikirim'],
            ['status_pesanan' => 'dibatalkan'],
            ['status_pesanan' => 'selesai'],
        ];

        foreach ($kasus as $n => $tambahan) {
            $id = $this->buatPesananProduk($customer, 'PSN-C-1'.$n, 60000, $tambahan);
            $sebelum = DB::table('pesanan_produk')->where('id', $id)->value('status_pesanan');

            $this->actingAs($pemilik)->post(route('pesanan.batalkan', $id))->assertForbidden();

            $this->assertSame($sebelum, DB::table('pesanan_produk')->where('id', $id)->value('status_pesanan'));
        }
    }

    public function test_a_scheduled_service_order_cannot_be_cancelled_by_the_customer(): void
    {
        [$customer, $akun] = $this->buatCustomer('Erika Putri');
        $id = $this->buatPesananJasa($customer, 'PSN-C-200', 90000, ['status_pesanan' => 'dijadwalkan']);

        $this->actingAs($this->akun($akun))->post(route('pesanan-jasa.batalkan', $id))->assertForbidden();

        $this->assertDatabaseHas('pesanan_jasa', ['id' => $id, 'status_pesanan' => 'dijadwalkan']);
    }

    public function test_only_the_owner_can_cancel(): void
    {
        [$pemilik] = $this->buatCustomer('Farhan Ali');
        [, $lain] = $this->buatCustomer('Gina Maharani');
        $produk = $this->buatPesananProduk($pemilik, 'PSN-C-300', 60000);
        $jasa = $this->buatPesananJasa($pemilik, 'PSN-C-301', 90000);

        $penyusup = $this->akun($lain);
        $this->actingAs($penyusup)->post(route('pesanan.batalkan', $produk))->assertForbidden();
        $this->actingAs($penyusup)->post(route('pesanan-jasa.batalkan', $jasa))->assertForbidden();

        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $this->actingAs($admin)->post(route('pesanan.batalkan', $produk))->assertForbidden();

        $this->assertDatabaseHas('pesanan_produk', ['id' => $produk, 'status_pesanan' => 'diproses']);
        $this->assertDatabaseHas('pesanan_jasa', ['id' => $jasa, 'status_pesanan' => 'diproses']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post(route('pesanan.batalkan', 1))->assertRedirect(route('login'));
        $this->post(route('pesanan-jasa.batalkan', 1))->assertRedirect(route('login'));
    }

    public function test_the_cancel_button_only_shows_while_it_is_allowed(): void
    {
        [$customer, $akun] = $this->buatCustomer('Hendra Wijaya');
        $pemilik = $this->akun($akun);
        $bisa = $this->buatPesananProduk($customer, 'PSN-C-400', 60000);
        $sudahBukti = $this->buatPesananProduk($customer, 'PSN-C-401', 60000, ['status_pembayaran' => 'menunggu_verifikasi']);
        $jasa = $this->buatPesananJasa($customer, 'PSN-C-402', 90000);

        $this->withoutVite()->actingAs($pemilik)
            ->get(route('pesanan.show', $bisa))
            ->assertOk()
            ->assertSee('Batalkan Pesanan');

        $this->withoutVite()->actingAs($pemilik)
            ->get(route('pesanan.show', $sudahBukti))
            ->assertOk()
            ->assertDontSee('Batalkan Pesanan');

        $this->withoutVite()->actingAs($pemilik)
            ->get(route('pesanan-jasa.show', $jasa))
            ->assertOk()
            ->assertSee('Batalkan Pesanan');
    }

    public function test_a_cancelled_order_cannot_receive_a_payment_proof(): void
    {
        [$customer, $akun] = $this->buatCustomer('Intan Permata');
        $id = $this->buatPesananProduk($customer, 'PSN-C-500', 60000);
        $pemilik = $this->akun($akun);

        $this->actingAs($pemilik)->post(route('pesanan.batalkan', $id))->assertSessionHas('status');
        $this->actingAs($pemilik)->post(route('pesanan.bukti.simpan', $id))->assertForbidden();
    }

    private function akun(int $id): Akun
    {
        return Akun::findOrFail($id);
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
