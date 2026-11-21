<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminKategoriTest extends TestCase
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

        Schema::create('kategori', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tipe');
        });

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->unsignedInteger('stok')->default(0);
        });

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.kategori.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_manage_categories(): void
    {
        $id = $this->buatKategori('Pot', 'produk');

        foreach (['customer', 'kurir'] as $role) {
            $akun = $this->buatAkun($role);

            $this->actingAs($akun)->get(route('admin.kategori.index'))->assertForbidden();
            $this->actingAs($akun)->get(route('admin.kategori.create'))->assertForbidden();
            $this->actingAs($akun)->post(route('admin.kategori.store'), ['nama' => 'Baru', 'tipe' => 'produk'])->assertForbidden();
            $this->actingAs($akun)->put(route('admin.kategori.update', $id), ['nama' => 'Ubah'])->assertForbidden();
            $this->actingAs($akun)->delete(route('admin.kategori.destroy', $id))->assertForbidden();
        }

        $this->assertDatabaseHas('kategori', ['id' => $id, 'nama' => 'Pot']);
        $this->assertDatabaseMissing('kategori', ['nama' => 'Baru']);
    }

    public function test_admin_sees_categories_with_usage_and_can_filter_by_type(): void
    {
        $pot = $this->buatKategori('Pot dan Media Tanam', 'produk');
        $this->buatKategori('Perawatan Taman', 'jasa');
        DB::table('produk')->insert([
            ['kategori_id' => $pot, 'nama' => 'Pot A'],
            ['kategori_id' => $pot, 'nama' => 'Pot B'],
        ]);

        $admin = $this->buatAkun('admin');

        $this->withoutVite()->actingAs($admin)
            ->get(route('admin.kategori.index'))
            ->assertOk()
            ->assertSee('Pot dan Media Tanam')
            ->assertSee('2 produk')
            ->assertSee('Perawatan Taman');

        $this->withoutVite()->actingAs($admin)
            ->get(route('admin.kategori.index', ['tipe' => 'jasa']))
            ->assertOk()
            ->assertSee('Perawatan Taman')
            ->assertDontSee('Pot dan Media Tanam');
    }

    public function test_admin_can_create_a_category(): void
    {
        $this->withoutVite()->actingAs($this->buatAkun('admin'))
            ->get(route('admin.kategori.create'))
            ->assertOk()
            ->assertSee('Tambah Kategori');

        $this->actingAs($this->buatAkun('admin'))
            ->post(route('admin.kategori.store'), ['nama' => 'Tanaman Hias', 'tipe' => 'produk'])
            ->assertRedirect(route('admin.kategori.index', ['tipe' => 'produk']))
            ->assertSessionHas('status', 'Kategori berhasil ditambahkan.');

        $this->assertDatabaseHas('kategori', ['nama' => 'Tanaman Hias', 'tipe' => 'produk']);
    }

    public function test_creation_requires_a_valid_name_and_type(): void
    {
        $admin = $this->buatAkun('admin');

        $this->actingAs($admin)->post(route('admin.kategori.store'), ['nama' => '', 'tipe' => 'produk'])
            ->assertSessionHasErrors('nama');
        $this->actingAs($admin)->post(route('admin.kategori.store'), ['nama' => 'Aneh', 'tipe' => 'lainnya'])
            ->assertSessionHasErrors('tipe');
        $this->actingAs($admin)->post(route('admin.kategori.store'), ['nama' => str_repeat('a', 101), 'tipe' => 'produk'])
            ->assertSessionHasErrors('nama');

        $this->assertSame(0, DB::table('kategori')->count());
    }

    public function test_the_same_name_is_rejected_within_a_type_but_allowed_across_types(): void
    {
        $this->buatKategori('Perawatan', 'produk');
        $admin = $this->buatAkun('admin');

        $this->actingAs($admin)->post(route('admin.kategori.store'), ['nama' => 'Perawatan', 'tipe' => 'produk'])
            ->assertSessionHasErrors('nama');

        $this->actingAs($admin)->post(route('admin.kategori.store'), ['nama' => 'Perawatan', 'tipe' => 'jasa'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, DB::table('kategori')->where('nama', 'Perawatan')->count());
    }

    public function test_admin_can_rename_a_category_but_not_change_its_type(): void
    {
        $id = $this->buatKategori('Pupuk', 'produk');
        $admin = $this->buatAkun('admin');

        $this->withoutVite()->actingAs($admin)
            ->get(route('admin.kategori.edit', $id))
            ->assertOk()
            ->assertSee('Ubah Kategori')
            ->assertSee('Pupuk');

        $this->actingAs($admin)
            ->put(route('admin.kategori.update', $id), ['nama' => 'Pupuk dan Nutrisi', 'tipe' => 'jasa'])
            ->assertRedirect(route('admin.kategori.index', ['tipe' => 'produk']))
            ->assertSessionHas('status', 'Kategori berhasil diperbarui.');

        $this->assertDatabaseHas('kategori', ['id' => $id, 'nama' => 'Pupuk dan Nutrisi', 'tipe' => 'produk']);
    }

    public function test_rename_rejects_a_duplicate_but_accepts_its_own_name(): void
    {
        $id = $this->buatKategori('Pupuk', 'produk');
        $this->buatKategori('Pot', 'produk');
        $admin = $this->buatAkun('admin');

        $this->actingAs($admin)->put(route('admin.kategori.update', $id), ['nama' => 'Pot'])
            ->assertSessionHasErrors('nama');

        $this->actingAs($admin)->put(route('admin.kategori.update', $id), ['nama' => 'Pupuk'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $produk = $this->buatKategori('Pot', 'produk');
        $jasa = $this->buatKategori('Pangkas', 'jasa');
        DB::table('produk')->insert(['kategori_id' => $produk, 'nama' => 'Pot A']);
        DB::table('jasa')->insert(['kategori_id' => $jasa, 'nama' => 'Pangkas Rumput']);
        $admin = $this->buatAkun('admin');

        $this->actingAs($admin)->delete(route('admin.kategori.destroy', $produk))
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih dipakai oleh 1 produk.');
        $this->actingAs($admin)->delete(route('admin.kategori.destroy', $jasa))
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih dipakai oleh 1 jasa.');

        $this->assertSame(2, DB::table('kategori')->count());
    }

    public function test_an_unused_category_can_be_deleted(): void
    {
        $id = $this->buatKategori('Kosong', 'produk');

        $this->actingAs($this->buatAkun('admin'))
            ->delete(route('admin.kategori.destroy', $id))
            ->assertRedirect(route('admin.kategori.index', ['tipe' => 'produk']))
            ->assertSessionHas('status', 'Kategori berhasil dihapus.');

        $this->assertDatabaseMissing('kategori', ['id' => $id]);
    }

    public function test_the_category_menu_appears_in_the_admin_sidebar(): void
    {
        $this->withoutVite()->actingAs($this->buatAkun('admin'))
            ->get(route('admin.kategori.index'))
            ->assertOk()
            ->assertSee(route('admin.kategori.index'), false);
    }

    private function buatKategori(string $nama, string $tipe): int
    {
        return DB::table('kategori')->insertGetId(['nama' => $nama, 'tipe' => $tipe]);
    }

    private function buatAkun(string $role): Akun
    {
        $id = DB::table('akun')->insertGetId([
            'nama' => 'Akun '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);

        return Akun::findOrFail($id);
    }
}
