<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminJasaTest extends TestCase
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

        Schema::create('jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->string('satuan');
            $table->string('foto')->nullable();
        });

        Schema::create('detail_pesanan_jasa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jasa_id');
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.jasa.create'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_open_the_service_form_with_service_categories(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        DB::table('kategori')->insert([
            ['nama' => 'Tanaman Hias', 'tipe' => 'produk'],
            ['nama' => 'Jasa Perawatan', 'tipe' => 'jasa'],
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.jasa.create'))
            ->assertOk()
            ->assertSee('Input Jasa')
            ->assertSee('Jasa Perawatan')
            ->assertDontSee('Tanaman Hias');
    }

    public function test_admin_can_browse_and_edit_services(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Jasa Perawatan',
            'tipe' => 'jasa',
        ]);
        $serviceId = DB::table('jasa')->insertGetId([
            'kategori_id' => $categoryId,
            'nama' => 'Perawatan Rutin',
            'deskripsi' => 'Penyiraman dan pemupukan berkala.',
            'harga' => 75000,
            'satuan' => 'kunjungan',
            'foto' => null,
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.jasa.index'))
            ->assertOk()
            ->assertSee('Perawatan Rutin')
            ->assertSee(route('admin.jasa.edit', $serviceId));

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.jasa.edit', $serviceId))
            ->assertOk()
            ->assertSee('Edit Jasa')
            ->assertSee('value="Perawatan Rutin"', false);

        $this->actingAs($admin)
            ->put(route('admin.jasa.update', $serviceId), [
                'nama' => 'Perawatan Premium',
                'kategori_id' => $categoryId,
                'deskripsi' => 'Perawatan tanaman indoor lengkap.',
                'harga' => 90000,
                'satuan' => 'kunjungan',
            ])
            ->assertRedirect(route('admin.jasa.index'))
            ->assertSessionHas('status', 'Jasa berhasil diperbarui.');

        $this->assertDatabaseHas('jasa', [
            'id' => $serviceId,
            'nama' => 'Perawatan Premium',
            'harga' => 90000,
            'satuan' => 'kunjungan',
        ]);
    }

    public function test_non_admin_users_cannot_open_or_submit_service_form(): void
    {
        $customer = $this->createAccount('Pelanggan', 'customer');

        $this->actingAs($customer)
            ->get(route('admin.jasa.create'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.jasa.store'), [
                'nama' => 'Perawatan Rutin',
                'harga' => 75000,
                'satuan' => 'kunjungan',
            ])
            ->assertForbidden();

        $serviceId = DB::table('jasa')->insertGetId([
            'nama' => 'Perawatan Rutin',
            'harga' => 75000,
            'satuan' => 'kunjungan',
        ]);

        $this->actingAs($customer)
            ->put(route('admin.jasa.update', $serviceId), [
                'nama' => 'Perawatan Baru',
                'harga' => 90000,
                'satuan' => 'kunjungan',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->delete(route('admin.jasa.destroy', $serviceId))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_service_with_a_photo(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Jasa Perawatan',
            'tipe' => 'jasa',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.jasa.create'))
            ->post(route('admin.jasa.store'), [
                'nama' => 'Perawatan Rutin',
                'kategori_id' => $categoryId,
                'deskripsi' => 'Penyiraman dan pemupukan berkala.',
                'harga' => 75000,
                'satuan' => 'kunjungan',
                'foto' => UploadedFile::fake()->image('perawatan.jpg'),
            ]);

        $response->assertRedirect(route('admin.jasa.index'))
            ->assertSessionHas('status', 'Jasa berhasil ditambahkan.');

        $service = DB::table('jasa')->where('nama', 'Perawatan Rutin')->first();
        $this->assertNotNull($service);
        $this->assertSame($categoryId, $service->kategori_id);
        $this->assertSame(75000, $service->harga);
        $this->assertSame('kunjungan', $service->satuan);
        $this->assertNotEmpty($service->foto);
        $this->assertFileExists(public_path('images/jasa/'.$service->foto));

        File::delete(public_path('images/jasa/'.$service->foto));
    }

    public function test_service_creation_rejects_a_product_category(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Tanaman Hias',
            'tipe' => 'produk',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.jasa.create'))
            ->post(route('admin.jasa.store'), [
                'nama' => 'Perawatan Rutin',
                'kategori_id' => $categoryId,
                'harga' => 75000,
                'satuan' => 'kunjungan',
            ])
            ->assertRedirect(route('admin.jasa.create'))
            ->assertSessionHasErrors('kategori_id');

        $this->assertDatabaseMissing('jasa', ['nama' => 'Perawatan Rutin']);
    }

    public function test_admin_can_delete_a_service_and_its_uploaded_photo(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $photoName = 'admin-service-delete-test.jpg';
        $photoPath = public_path('images/jasa/'.$photoName);
        File::ensureDirectoryExists(dirname($photoPath));
        File::put($photoPath, 'test image');
        $serviceId = DB::table('jasa')->insertGetId([
            'nama' => 'Jasa untuk dihapus',
            'harga' => 50000,
            'satuan' => 'kunjungan',
            'foto' => $photoName,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.jasa.destroy', $serviceId))
            ->assertRedirect(route('admin.jasa.index'))
            ->assertSessionHas('status', 'Jasa berhasil dihapus.');

        $this->assertDatabaseMissing('jasa', ['id' => $serviceId]);
        $this->assertFileDoesNotExist($photoPath);
    }

    public function test_admin_cannot_delete_a_service_used_in_an_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $serviceId = DB::table('jasa')->insertGetId([
            'nama' => 'Jasa dalam pesanan',
            'harga' => 50000,
            'satuan' => 'kunjungan',
        ]);
        DB::table('detail_pesanan_jasa')->insert(['jasa_id' => $serviceId]);

        $this->actingAs($admin)
            ->delete(route('admin.jasa.destroy', $serviceId))
            ->assertRedirect(route('admin.jasa.index'))
            ->assertSessionHas('error', 'Jasa tidak dapat dihapus karena sudah tercatat dalam pesanan.');

        $this->assertDatabaseHas('jasa', ['id' => $serviceId]);
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
