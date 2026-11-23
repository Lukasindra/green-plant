<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPengaturanTest extends TestCase
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

        Schema::create('pengaturan', function (Blueprint $table): void {
            $table->id();
            $table->string('kunci')->unique();
            $table->text('nilai');
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.pengaturan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_view_settings(): void
    {
        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('admin.pengaturan.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_default_store_settings_when_table_is_empty(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pengaturan.index'))
            ->assertOk()
            ->assertSee('Rp 15.000')
            ->assertSee('Midtrans Snap');
    }

    public function test_admin_can_save_store_settings(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.simpan'), [
                'ongkir' => 20000,
            ])
            ->assertRedirect(route('admin.pengaturan.index'))
            ->assertSessionHas('status', 'Pengaturan toko disimpan.');

        $this->assertDatabaseHas('pengaturan', ['kunci' => 'ongkir', 'nilai' => json_encode(20000)]);

        // Dilihat ulang: nilai tersimpan yang tampil
        $this->actingAs($admin)
            ->get(route('admin.pengaturan.index'))
            ->assertOk()
            ->assertSee('Rp 20.000');
    }

    public function test_negative_ongkir_is_rejected(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.simpan'), [
                'ongkir' => -500,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('ongkir');
    }

    private function createAccount(string $name, string $role): Akun
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        return Akun::findOrFail($akunId);
    }
}
