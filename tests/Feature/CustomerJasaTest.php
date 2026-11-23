<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jasa;
use App\Models\PesananJasa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerJasaTest extends TestCase
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
            $table->foreignId('akun_id')->constrained('akun')->cascadeOnDelete();
            $table->text('alamat');
        });

        Schema::create('kategori', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tipe');
        });

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 12, 0);
            $table->string('satuan');
            $table->string('foto')->nullable();
        });

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->foreignId('customer_id')->constrained('customer')->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->date('tanggal_pesan');
            $table->decimal('total_bayar', 12, 0);
            $table->string('status_pembayaran', 20)->default('unpaid');
            $table->string('status_pesanan', 20)->default('menunggu_konfirmasi');
            $table->text('alamat_lokasi');
            $table->datetime('jadwal_pengerjaan')->nullable();
            $table->datetime('tanggal_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->date('tanggal_bayar')->nullable();
        });

        Schema::create('detail_pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pesanan_jasa_id')->constrained('pesanan_jasa')->cascadeOnDelete();
            $table->foreignId('jasa_id')->constrained('jasa')->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('harga', 12, 0);
            $table->decimal('subtotal', 12, 0);
        });
    }

    private function createAccount(string $nama, string $role, string $email = null): Akun
    {
        $akun = new Akun([
            'nama'     => $nama,
            'email'    => $email ?? strtolower(str_replace(' ', '.', $nama)) . '@contoh.test',
            'password' => 'rahasia123',
        ]);
        $akun->role = $role;
        $akun->save();

        if ($role === 'customer') {
            DB::table('customer')->insert(['akun_id' => $akun->id, 'alamat' => 'Jl. Kenanga No. 7, Jakarta']);
        }

        return $akun;
    }

    private function buatJasa(array $data = []): Jasa
    {
        $jasa = new Jasa();
        $jasa->kategori_id = null;
        $jasa->nama        = 'Perawatan Taman Bulanan';
        $jasa->deskripsi   = 'Pangkas rumpun, pupuk, dan atur ulang tanaman hias.';
        $jasa->harga       = 150000;
        $jasa->satuan      = 'sesi';
        $jasa->forceFill($data)->save();

        return $jasa;
    }

    private function buatPesanan(Akun $pemilik, array $data = []): PesananJasa
    {
        $pesanan = new PesananJasa([
            'kode'              => 'JSA-' . uniqid(),
            'customer_id'       => $pemilik->customer->id,
            'tanggal_pesan'     => now()->toDateString(),
            'alamat_lokasi'     => 'Jl. Kenanga No. 7, Jakarta',
        ]);
        $pesanan->total_bayar = 150000;
        $pesanan->forceFill($data)->save();

        return $pesanan;
    }

    public function test_tamu_dapat_melihat_katalog_jasa(): void
    {
        $jasa = $this->buatJasa();

        $this->get(route('jasa.index'))
            ->assertOk()
            ->assertSee($jasa->nama)
            ->assertSee('Rp 150.000');
    }

    public function test_tamu_diarahkan_ke_login_saat_memesan(): void
    {
        $jasa = $this->buatJasa();

        $this->post(route('jasa.pesan', $jasa), ['qty' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_akun_non_customer_ditolak_memesan(): void
    {
        $this->actingAs($this->createAccount('Admin Green Plant', 'admin'))
            ->post(route('jasa.pesan', $this->buatJasa()), [
                'qty' => 1, 'jadwal' => '2030-01-01T10:00:00',
            ])
            ->assertForbidden();
    }

    public function test_customer_dapat_memesan_jasa(): void
    {
        $customer = $this->createAccount('Bu Sinta', 'customer');
        $jasa     = $this->buatJasa();

        $this->actingAs($customer)
            ->post(route('jasa.pesan', $jasa), [
                'qty'               => 3,
                'jadwal'            => '2030-01-05T10:00:00',
                'alamat_lokasi'     => 'Jl. Melati No. 12, Depok',
                'catatan'           => 'Pangkas pendek, tanah kering.',
            ])
            ->assertRedirectContains('pembayaran/');

        $pesanan = PesananJasa::query()->firstOrFail();
        $this->assertSame('Jl. Melati No. 12, Depok', $pesanan->alamat_lokasi);
        $this->assertSame(450000, (int) $pesanan->total_bayar);
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
        $this->assertSame('menunggu_pembayaran', $pesanan->status_pesanan);
        $this->assertSame($customer->customer->id, $pesanan->customer_id);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{4,}$/', $pesanan->invoice_number);
        $this->assertNotNull($pesanan->snap_token);

        $this->assertDatabaseHas('detail_pesanan_jasa', [
            'pesanan_jasa_id' => $pesanan->id,
            'jasa_id'         => $jasa->id,
            'qty'             => 3,
            'harga'           => 150000,
            'subtotal'        => 450000,
        ]);
    }

    public function test_pemesanan_gagal_tanpa_jadwal_dengan_qty_tidak_valid(): void
    {
        $this->actingAs($this->createAccount('Bu Sinta', 'customer'))
            ->post(route('jasa.pesan', $this->buatJasa()), [
                'qty'           => 99,
                'alamat_lokasi' => 'Jl. Melati No. 12, Depok',
            ])
            ->assertSessionHasErrors(['jadwal', 'qty']);

        $this->assertDatabaseCount('pesanan_jasa', 0);
    }

    public function test_customer_melihat_pesanan_jasanya_sendiri(): void
    {
        $customer = $this->createAccount('Bu Sinta', 'customer');
        $pesanan  = $this->buatPesanan($customer);

        $this->actingAs($customer)
            ->get(route('pesanan-jasa.index'))
            ->assertOk()
            ->assertSee($pesanan->kode);
    }

    public function test_pemilik_dapat_bayar_ulang_dengan_token_baru(): void
    {
        $customer = $this->createAccount('Bu Sinta', 'customer');
        $pesanan  = $this->buatPesanan($customer, [
            'invoice_number' => 'INV-20261118-TEST',
            'snap_token'     => 'token-lama',
            'status_pembayaran' => 'failed',
        ]);

        $this->actingAs($customer)
            ->post(route('payment.retry', ['tipe' => 'jasa', 'pesanan' => $pesanan->id]))
            ->assertRedirect(route('payment.show', ['tipe' => 'jasa', 'pesanan' => $pesanan->id]))
            ->assertSessionHas('status');

        $pesanan->refresh();
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
        $this->assertNotNull($pesanan->snap_token);
        $this->assertNotSame('token-lama', $pesanan->snap_token);

        // Tetap satu record pesanan, invoice tidak berubah
        $this->assertDatabaseCount('pesanan_jasa', 1);
        $this->assertSame('INV-20261118-TEST', $pesanan->invoice_number);
    }

    public function test_customer_lain_tidak_bisa_bayar_ulang_pesanan_orang_lain(): void
    {
        $pemilik  = $this->createAccount('Bu Sinta', 'customer');
        $pesanan  = $this->buatPesanan($pemilik, ['snap_token' => 'token-lama']);
        $penyusup = $this->createAccount('Bu Lain', 'customer', 'bu.lain@contoh.test');

        $this->actingAs($penyusup)
            ->post(route('payment.retry', ['tipe' => 'jasa', 'pesanan' => $pesanan->id]))
            ->assertForbidden();

        $pesanan->refresh();
        $this->assertSame('token-lama', $pesanan->snap_token);
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
    }

    public function test_pembayaran_sukses_menandai_pesanan_jasa_lunas(): void
    {
        $customer = $this->createAccount('Bu Sinta', 'customer');
        $pesanan  = $this->buatPesanan($customer, [
            'invoice_number' => 'INV-20261118-SOKS',
            'snap_token'     => 'snap-token-test-abc',
            'status_pesanan' => 'menunggu_pembayaran',
        ]);

        $this->actingAs($customer)
            ->get(route('payment.success', ['tipe' => 'jasa', 'pesanan' => $pesanan->id]))
            ->assertRedirect(route('pesanan-jasa.show', $pesanan->id))
            ->assertSessionHas('status');

        $pesanan->refresh();
        $this->assertSame('paid', $pesanan->status_pembayaran);
        $this->assertNotNull($pesanan->tanggal_bayar);
    }

    public function test_halaman_pembayaran_hanya_untuk_pemilik_pesanan(): void
    {
        $pemilik  = $this->createAccount('Bu Sinta', 'customer');
        $pesanan  = $this->buatPesanan($pemilik, ['snap_token' => 'token-abc']);
        $penyusup = $this->createAccount('Bu Lain', 'customer', 'bu.lain@contoh.test');

        $this->actingAs($penyusup)
            ->get(route('payment.show', ['tipe' => 'jasa', 'pesanan' => $pesanan->id]))
            ->assertForbidden();
    }
}
