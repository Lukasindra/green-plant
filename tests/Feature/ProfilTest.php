<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfilTest extends TestCase
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
    }

    public function test_customer_can_view_and_update_their_profile(): void
    {
        [$akun, $customer] = $this->buatCustomer('Nama Lama');

        $this->withoutVite()
            ->actingAs($akun)
            ->get(route('profil.index'))
            ->assertOk()
            ->assertSee('Nama Lama');

        $this->actingAs($akun)
            ->put(route('profil.update'), [
                'nama' => 'Nama Baru',
                'no_hp' => '081234567890',
                'alamat' => 'Jl. Baru No. 10',
            ])
            ->assertSessionHas('status');

        $akun->refresh();
        $this->assertSame('Nama Baru', $akun->nama);
        $this->assertSame('081234567890', $akun->no_hp);
        $this->assertSame('Jl. Baru No. 10', DB::table('customer')->where('id', $customer)->value('alamat'));
    }

    public function test_customer_can_change_their_password_with_the_correct_current_password(): void
    {
        [$akun] = $this->buatCustomer('Ganti Password');

        $this->actingAs($akun)
            ->put(route('profil.password'), [
                'password_lama' => 'password',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('password-baru', $akun->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        [$akun] = $this->buatCustomer('Salah Password');

        $this->actingAs($akun)
            ->put(route('profil.password'), [
                'password_lama' => 'bukan-password',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertSessionHasErrors('password_lama');

        $this->assertTrue(Hash::check('password', $akun->fresh()->password));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('profil.index'))->assertRedirect(route('login'));
    }

    private function buatCustomer(string $nama): array
    {
        $akun = new Akun([
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
        ]);
        $akun->role = 'customer';
        $akun->save();

        $customerId = DB::table('customer')->insertGetId([
            'akun_id' => $akun->id,
            'alamat' => 'Jl. Lama 1',
        ]);

        return [$akun, $customerId];
    }
}
