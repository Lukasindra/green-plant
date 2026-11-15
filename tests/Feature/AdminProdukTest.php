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

class AdminProdukTest extends TestCase
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
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga');
            $table->unsignedInteger('stok');
            $table->string('foto')->nullable();
        });

        Schema::create('detail_pesanan_produk', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('produk_id');
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.produk.create'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_open_the_product_form_with_product_categories(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        DB::table('kategori')->insert([
            ['nama' => 'Tanaman Hias', 'tipe' => 'produk'],
            ['nama' => 'Jasa Perawatan', 'tipe' => 'jasa'],
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.produk.create'))
            ->assertOk()
            ->assertSee('Input Produk')
            ->assertSee('Tanaman Hias')
            ->assertDontSee('Jasa Perawatan');
    }

    public function test_admin_can_browse_and_edit_products(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Tanaman Hias',
            'tipe' => 'produk',
        ]);
        $productId = DB::table('produk')->insertGetId([
            'kategori_id' => $categoryId,
            'nama' => 'Monstera',
            'deskripsi' => 'Tanaman hias.',
            'harga' => 25000,
            'stok' => 8,
            'foto' => null,
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.produk.index'))
            ->assertOk()
            ->assertSee('Monstera')
            ->assertSee(route('admin.produk.edit', $productId));

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.produk.edit', $productId))
            ->assertOk()
            ->assertSee('Edit Produk')
            ->assertSee('value="Monstera"', false);

        $this->actingAs($admin)
            ->put(route('admin.produk.update', $productId), [
                'nama' => 'Monstera Deliciosa',
                'kategori_id' => $categoryId,
                'deskripsi' => 'Tanaman indoor.',
                'harga' => 30000,
                'stok' => 4,
            ])
            ->assertRedirect(route('admin.produk.index'))
            ->assertSessionHas('status', 'Produk berhasil diperbarui.');

        $this->assertDatabaseHas('produk', [
            'id' => $productId,
            'nama' => 'Monstera Deliciosa',
            'harga' => 30000,
            'stok' => 4,
        ]);
    }

    public function test_non_admin_users_cannot_open_or_submit_product_form(): void
    {
        $customer = $this->createAccount('Pelanggan', 'customer');

        $this->actingAs($customer)
            ->get(route('admin.produk.create'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.produk.store'), [
                'nama' => 'Monstera',
                'harga' => 25000,
                'stok' => 5,
            ])
            ->assertForbidden();

        $productId = DB::table('produk')->insertGetId([
            'nama' => 'Monstera',
            'harga' => 25000,
            'stok' => 5,
        ]);

        $this->actingAs($customer)
            ->put(route('admin.produk.update', $productId), [
                'nama' => 'Monstera Baru',
                'harga' => 30000,
                'stok' => 4,
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->delete(route('admin.produk.destroy', $productId))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_product_with_a_photo(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Tanaman Hias',
            'tipe' => 'produk',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.produk.create'))
            ->post(route('admin.produk.store'), [
                'nama' => 'Monstera',
                'kategori_id' => $categoryId,
                'deskripsi' => 'Tanaman hias untuk ruangan.',
                'harga' => 25000,
                'stok' => 8,
                'foto' => UploadedFile::fake()->image('monstera.jpg'),
            ]);

        $response->assertRedirect(route('admin.produk.index'))
            ->assertSessionHas('status', 'Produk berhasil ditambahkan.');

        $product = DB::table('produk')->where('nama', 'Monstera')->first();
        $this->assertNotNull($product);
        $this->assertSame($categoryId, $product->kategori_id);
        $this->assertSame(25000, $product->harga);
        $this->assertSame(8, $product->stok);
        $this->assertNotEmpty($product->foto);
        $this->assertFileExists(public_path('images/produk/'.$product->foto));

        File::delete(public_path('images/produk/'.$product->foto));
    }

    public function test_product_creation_rejects_a_service_category(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $categoryId = DB::table('kategori')->insertGetId([
            'nama' => 'Jasa Perawatan',
            'tipe' => 'jasa',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.produk.create'))
            ->post(route('admin.produk.store'), [
                'nama' => 'Monstera',
                'kategori_id' => $categoryId,
                'harga' => 25000,
                'stok' => 5,
            ])
            ->assertRedirect(route('admin.produk.create'))
            ->assertSessionHasErrors('kategori_id');

        $this->assertDatabaseMissing('produk', ['nama' => 'Monstera']);
    }

    public function test_admin_can_delete_a_product_and_its_uploaded_photo(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $photoName = 'admin-product-delete-test.jpg';
        $photoPath = public_path('images/produk/'.$photoName);
        File::ensureDirectoryExists(dirname($photoPath));
        File::put($photoPath, 'test image');
        $productId = DB::table('produk')->insertGetId([
            'nama' => 'Produk untuk dihapus',
            'harga' => 10000,
            'stok' => 1,
            'foto' => $photoName,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.produk.destroy', $productId))
            ->assertRedirect(route('admin.produk.index'))
            ->assertSessionHas('status', 'Produk berhasil dihapus.');

        $this->assertDatabaseMissing('produk', ['id' => $productId]);
        $this->assertFileDoesNotExist($photoPath);
    }

    public function test_admin_cannot_delete_a_product_used_in_an_order(): void
    {
        $admin = $this->createAccount('Admin Green Plant', 'admin');
        $productId = DB::table('produk')->insertGetId([
            'nama' => 'Produk dalam pesanan',
            'harga' => 10000,
            'stok' => 1,
        ]);
        DB::table('detail_pesanan_produk')->insert(['produk_id' => $productId]);

        $this->actingAs($admin)
            ->delete(route('admin.produk.destroy', $productId))
            ->assertRedirect(route('admin.produk.index'))
            ->assertSessionHas('error', 'Produk tidak dapat dihapus karena sudah tercatat dalam pesanan.');

        $this->assertDatabaseHas('produk', ['id' => $productId]);
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
