<?php

namespace Tests\Feature;

use App\Models\Akun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeranMiddlewareTest extends TestCase
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

        Route::middleware(['web', 'auth', 'role:admin'])->get('/uji-peran/admin', fn () => 'ok-admin');
        Route::middleware(['web', 'auth', 'role:admin,kurir'])->get('/uji-peran/staf', fn () => 'ok-staf');
    }

    public function test_guests_are_redirected_to_login_before_the_role_check(): void
    {
        $this->get('/uji-peran/admin')->assertRedirect(route('login'));
    }

    public function test_only_the_listed_role_may_pass(): void
    {
        $this->actingAs($this->buatAkun('admin'))->get('/uji-peran/admin')->assertOk()->assertSee('ok-admin');
        $this->actingAs($this->buatAkun('kurir'))->get('/uji-peran/admin')->assertForbidden();
        $this->actingAs($this->buatAkun('customer'))->get('/uji-peran/admin')->assertForbidden();
    }

    public function test_several_roles_can_be_listed(): void
    {
        $this->actingAs($this->buatAkun('admin'))->get('/uji-peran/staf')->assertOk();
        $this->actingAs($this->buatAkun('kurir'))->get('/uji-peran/staf')->assertOk();
        $this->actingAs($this->buatAkun('customer'))->get('/uji-peran/staf')->assertForbidden();
    }

    public function test_unknown_role_is_rejected(): void
    {
        $this->actingAs($this->buatAkun('tamu-aneh'))->get('/uji-peran/staf')->assertForbidden();
    }

    public function test_every_admin_route_requires_the_admin_role(): void
    {
        $this->actingAs($this->buatAkun('customer'));

        foreach (Route::getRoutes() as $rute) {
            if (! str_starts_with($rute->uri(), 'admin/')) {
                continue;
            }

            $this->assertContains('role:admin', $rute->gatherMiddleware(), 'Rute tanpa role:admin: '.$rute->uri());
        }
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
