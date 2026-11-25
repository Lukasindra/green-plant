<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LupaPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        Schema::create('akun', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->string('role');
            $table->string('remember_token')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_forgot_password_page_is_available(): void
    {
        $this->withoutVite()
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa Password');
    }

    public function test_reset_link_is_sent_for_a_registered_email(): void
    {
        $akun = $this->buatAkun('lupa@example.test');

        $this->post(route('password.email'), ['email' => $akun->email])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $akun->email]);
        Notification::assertSentTo($akun, ResetPasswordNotification::class);
    }

    public function test_unknown_email_gets_an_error_without_leaking_details(): void
    {
        $this->post(route('password.email'), ['email' => 'tidak-ada@example.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $akun = $this->buatAkun('reset@example.test');
        $token = Password::createToken($akun);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $akun->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-baru', $akun->fresh()->password));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $akun = $this->buatAkun('invalid@example.test');

        $this->post(route('password.update'), [
            'token' => 'token-palsu',
            'email' => $akun->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $akun->fresh()->password));
    }

    private function buatAkun(string $email): Akun
    {
        $akun = new Akun([
            'nama' => 'Pengguna',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $akun->role = 'customer';
        $akun->save();

        return $akun;
    }
}
