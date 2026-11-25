<?php

namespace Tests\Feature;

use App\Services\MidtransService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SinkronkanPembayaranTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('midtrans_order_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->unsignedInteger('total_bayar')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('diproses');
        });

        Schema::create('pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('midtrans_order_id')->nullable();
            $table->date('tanggal_pesan');
            $table->date('tanggal_bayar')->nullable();
            $table->unsignedInteger('total_bayar')->default(0);
            $table->string('status_pembayaran')->default('unpaid');
            $table->string('status_pesanan')->default('diproses');
        });
    }

    public function test_command_marks_settled_orders_as_paid(): void
    {
        $produk = $this->buatPesanan('pesanan_produk', 'PSN-P-400', 'INV-20261123-P01');
        $jasa   = $this->buatPesanan('pesanan_jasa', 'PSN-J-400', 'INV-20261123-J01');

        $this->artisan('pesanan:sinkronkan-pembayaran')
            ->expectsOutputToContain('Pesanan produk diperiksa: 1, lunas: 1')
            ->expectsOutputToContain('Pesanan jasa diperiksa: 1, lunas: 1')
            ->assertSuccessful();

        foreach (['pesanan_produk' => $produk, 'pesanan_jasa' => $jasa] as $tabel => $id) {
            $pesanan = DB::table($tabel)->where('id', $id)->first();
            $this->assertSame('paid', $pesanan->status_pembayaran);
            $this->assertNotNull($pesanan->tanggal_bayar);
        }
    }

    public function test_command_leaves_pending_orders_unpaid(): void
    {
        $id = $this->buatPesanan('pesanan_produk', 'PSN-P-401', 'INV-20261123-P02');

        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldReceive('status')->once()->andReturn([
                'status_code'        => '201',
                'transaction_status' => 'pending',
                'fraud_status'       => 'accept',
            ]);
        });

        $this->artisan('pesanan:sinkronkan-pembayaran')
            ->expectsOutputToContain('menunggu: 1')
            ->assertSuccessful();

        $pesanan = DB::table('pesanan_produk')->where('id', $id)->first();
        $this->assertSame('unpaid', $pesanan->status_pembayaran);
    }

    public function test_command_marks_expired_transaction_as_failed(): void
    {
        $id = $this->buatPesanan('pesanan_produk', 'PSN-P-402', 'INV-20261123-P03');

        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldReceive('status')->once()->andReturn([
                'status_code'        => '407',
                'transaction_status' => 'expire',
            ]);
        });

        $this->artisan('pesanan:sinkronkan-pembayaran')
            ->expectsOutputToContain('gagal: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('pesanan_produk', [
            'id'                => $id,
            'status_pembayaran' => 'failed',
        ]);
    }

    public function test_command_skips_orders_without_midtrans_order_id(): void
    {
        $id = $this->buatPesanan('pesanan_produk', 'PSN-P-403', null);

        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldNotReceive('status');
        });

        $this->artisan('pesanan:sinkronkan-pembayaran')
            ->expectsOutputToContain('Pesanan produk diperiksa: 0')
            ->assertSuccessful();

        $this->assertSame('unpaid', DB::table('pesanan_produk')->where('id', $id)->value('status_pembayaran'));
    }

    public function test_command_skips_cancelled_orders(): void
    {
        $id = $this->buatPesanan('pesanan_produk', 'PSN-P-404', 'INV-20261123-P04', [
            'status_pesanan' => 'dibatalkan',
        ]);

        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldNotReceive('status');
        });

        $this->artisan('pesanan:sinkronkan-pembayaran')->assertSuccessful();

        $this->assertDatabaseHas('pesanan_produk', [
            'id'                => $id,
            'status_pembayaran' => 'unpaid',
            'status_pesanan'    => 'dibatalkan',
        ]);
    }

    public function test_command_skips_orders_older_than_the_window(): void
    {
        $id = $this->buatPesanan('pesanan_produk', 'PSN-P-405', 'INV-20261123-P05', [
            'tanggal_pesan' => now()->subDays(10)->toDateString(),
        ]);

        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldNotReceive('status');
        });

        $this->artisan('pesanan:sinkronkan-pembayaran', ['--hari' => 7])
            ->expectsOutputToContain('Pesanan produk diperiksa: 0')
            ->assertSuccessful();

        $this->assertSame('unpaid', DB::table('pesanan_produk')->where('id', $id)->value('status_pembayaran'));
    }

    public function test_invalid_day_limit_is_rejected(): void
    {
        $this->artisan('pesanan:sinkronkan-pembayaran', ['--hari' => 0])
            ->expectsOutputToContain('Nilai --hari minimal 1.')
            ->assertFailed();
    }

    private function buatPesanan(string $tabel, string $kode, ?string $orderId, array $tambahan = []): int
    {
        return DB::table($tabel)->insertGetId(array_merge([
            'kode'              => $kode,
            'invoice_number'    => $orderId,
            'midtrans_order_id' => $orderId,
            'tanggal_pesan'     => now()->toDateString(),
            'total_bayar'       => 50000,
            'status_pembayaran' => 'unpaid',
            'status_pesanan'    => 'diproses',
        ], $tambahan));
    }
}
