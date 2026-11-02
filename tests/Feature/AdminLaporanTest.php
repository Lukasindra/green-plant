<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\MembuatSkemaErd;
use Tests\TestCase;

class AdminLaporanTest extends TestCase
{
    use MembuatSkemaErd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buatSkemaErd();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.laporan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_browse_reports(): void
    {
        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('admin.laporan.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_paid_orders_in_the_default_period(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $hariIni = now()->toDateString();

        $this->buatPesananProduk('PSN-P-300', 50000, ['status_pembayaran' => 'lunas', 'tanggal_pesan' => $hariIni]);
        $this->buatPesananJasa('PSN-J-300', 70000, ['status_pembayaran' => 'lunas', 'tanggal_pesan' => $hariIni]);
        // Belum lunas: tidak dihitung
        $this->buatPesananJasa('PSN-J-301', 99000, ['status_pembayaran' => 'menunggu_bayar', 'tanggal_pesan' => $hariIni]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.laporan.index'))
            ->assertOk()
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 70.000')
            ->assertSee('Rp 120.000')
            ->assertSee('1')
            ->assertDontSee('Rp 99.000');
    }

    public function test_admin_can_filter_reports_by_date_range(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $kemarin = now()->subDay()->toDateString();

        $this->buatPesananProduk('PSN-P-310', 30000, [
            'status_pembayaran' => 'lunas',
            'tanggal_pesan' => $kemarin,
        ]);
        $this->buatPesananJasa('PSN-J-310', 60000, [
            'status_pembayaran' => 'lunas',
            'tanggal_pesan' => $kemarin,
        ]);

        // Rentang hanya kemarin
        $this->actingAs($admin)
            ->get(route('admin.laporan.index', [
                'awal' => $kemarin,
                'akhir' => $kemarin,
            ]))
            ->assertOk()
            ->assertSee('Rp 30.000')
            ->assertSee('Rp 60.000')
            ->assertSee('Rp 90.000');

        // Rentang besar bergeser: bukan lunas di rentang itu → dihitung nol
        $this->actingAs($admin)
            ->get(route('admin.laporan.index', [
                'awal' => now()->toDateString(),
                'akhir' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Tidak ada pesanan pada periode ini.');
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

    private function buatPesananProduk(string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_produk')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => 1,
            'tanggal_pesan' => now()->toDateString(),
            'metode_pembayaran' => 'transfer',
            'alamat_pengiriman' => 'Jl. Melati 8',
            'total_bayar' => $total,
        ], $tambahan));
    }

    private function buatPesananJasa(string $kode, int $total, array $tambahan = []): int
    {
        return DB::table('pesanan_jasa')->insertGetId(array_merge([
            'kode' => $kode,
            'customer_id' => 1,
            'tanggal_pesan' => now()->toDateString(),
            'metode_pembayaran' => 'qris',
            'alamat_lokasi' => 'Perum Griya Asri',
            'total_bayar' => $total,
        ], $tambahan));
    }
}
