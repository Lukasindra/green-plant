<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPenggunaTest extends TestCase
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
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.pengguna.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_browse_users(): void
    {
        $this->actingAs($this->createAccount('Pelanggan', 'customer'))
            ->get(route('admin.pengguna.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_users_and_can_filter_by_role(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $this->createAccount('Bayu Aji', 'customer', 'Bayu Aji');
        $this->createAccount('Rizal', 'kurir', 'Rizal Kurir');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.pengguna.index'))
            ->assertOk()
            ->assertSee('Admin Green Plant')
            ->assertSee('Bayu Aji')
            ->assertSee('Rizal');

        $this->actingAs($admin)
            ->get(route('admin.pengguna.index', ['role' => 'kurir']))
            ->assertOk()
            ->assertSee('Rizal')
            ->assertDontSee('Bayu Aji');

        $this->actingAs($admin)
            ->get(route('admin.pengguna.index', ['q' => 'bayu']))
            ->assertOk()
            ->assertSee('Bayu Aji')
            ->assertDontSee('Rizal');
    }

    public function test_admin_can_create_a_courier_account(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');

        $this->actingAs($admin)
            ->post(route('admin.pengguna.store'), [
                'nama' => 'Budi Santoso',
                'email' => 'budi@example.test',
                'role' => 'kurir',
                'password' => 'rahasia123',
                'no_hp' => '081234567890',
                'username' => 'budi_kurir',
            ])
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('status', 'Pengguna Budi Santoso (kurir) ditambahkan.');

        $akunId = DB::table('akun')->where('email', 'budi@example.test')->value('id');
        $this->assertDatabaseHas('karyawan', [
            'akun_id' => $akunId,
            'username' => 'budi_kurir',
        ]);
    }

    public function test_admin_can_edit_user_and_change_role(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $id = $this->createAccount('Citra Ayu', 'customer', 'Citra Ayu')->id;

        $this->actingAs($admin)
            ->put(route('admin.pengguna.update', ['pengguna' => $id]), [
                'nama' => 'Citra Ayu Dewi',
                'email' => 'citra.dewi@example.test',
                'role' => 'kurir',
                'password' => '',
                'no_hp' => '081288990011',
                'username' => 'citra_kurir',
            ])
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('status', 'Pengguna diperbarui.');

        $this->assertDatabaseHas('akun', [
            'id' => $id,
            'nama' => 'Citra Ayu Dewi',
            'email' => 'citra.dewi@example.test',
            'role' => 'kurir',
        ]);

        // baris customer dibersihkan, baris karyawan dibuat
        $this->assertDatabaseMissing('customer', ['akun_id' => $id]);
        $this->assertDatabaseHas('karyawan', [
            'akun_id' => $id,
            'username' => 'citra_kurir',
        ]);
    }

    public function test_admin_cannot_modify_own_account(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');

        $this->actingAs($admin)
            ->put(route('admin.pengguna.update', ['pengguna' => $admin->id]), [
                'nama' => 'Admin Berubah',
                'email' => 'admin@example.test',
                'role' => 'admin',
                'password' => '',
            ])
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('error', 'Anda tidak dapat mengubah akun Anda sendiri.');

        $this->actingAs($admin)
            ->delete(route('admin.pengguna.destroy', ['pengguna' => $admin->id]))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun Anda sendiri.');

        $this->assertDatabaseHas('akun', ['id' => $admin->id, 'nama' => 'Admin Green Plant']);
    }

    public function test_admin_can_delete_a_user(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $id = $this->createAccount('Dodi Rahmat', 'customer', 'Dodi Rahmat')->id;

        $this->actingAs($admin)
            ->delete(route('admin.pengguna.destroy', ['pengguna' => $id]))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('status', 'Pengguna Dodi Rahmat dihapus.');

        $this->assertDatabaseMissing('akun', ['id' => $id]);
        $this->assertDatabaseMissing('customer', ['akun_id' => $id]);
    }

    private function createAccount(string $name, string $role, ?string $identityExtra = null): Akun
    {
        $akunId = DB::table('akun')->insertGetId([
            'nama' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        if ($role === 'customer') {
            DB::table('customer')->insert(['akun_id' => $akunId]);
        } elseif ($role === 'kurir') {
            DB::table('karyawan')->insert(['akun_id' => $akunId, 'username' => $identityExtra ?? $name]);
        }

        return Akun::findOrFail($akunId);
    }
}
