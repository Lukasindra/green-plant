<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Customer;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    private const ROLES = ['admin', 'kurir', 'customer'];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $role = $this->roleValid($request->input('role'));
        $kata = trim((string) $request->input('q', ''));

        $pengguna = Akun::query()
            ->orderByDesc('id')
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($kata !== '', fn ($q) => $q->where(function ($c) use ($kata) {
                $c->where('nama', 'like', "%{$kata}%")
                    ->orWhere('email', 'like', "%{$kata}%");
            }))
            ->paginate(15)
            ->withQueryString();

        return view('admin.pengguna.index', [
            'pengguna' => $pengguna,
            'role' => $role,
            'kata' => $kata,
            'daftarRole' => self::ROLES,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.pengguna.create', [
            'daftarRole' => self::ROLES,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:akun,email'],
            'role' => ['required', 'in:admin,kurir,customer'],
            'password' => ['required', 'string', 'min:6'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'username' => ['nullable', 'string', 'max:30', 'required_if:role,kurir', 'unique:karyawan,username'],
        ], [
            'email.unique' => 'Email sudah digunakan.',
            'username.required_if' => 'Username kurir wajib diisi.',
            'username.unique' => 'Username kurir sudah digunakan.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $akun = DB::transaction(function () use ($data) {
            $akun = new Akun([
                'nama' => $data['nama'],
                'email' => $data['email'],
                // password otomatis ter-hash lewat cast model Akun
                'password' => $data['password'],
                'no_hp' => $data['no_hp'] ?? null,
            ]);
            $akun->role = $data['role'];
            $akun->save();
            $this->sinkronkanTabelAnak($akun, $data['username'] ?? null);

            return $akun;
        });

        return redirect()
            ->route('admin.pengguna.index')
            ->with('status', "Pengguna {$akun->nama} ({$akun->role}) ditambahkan.");
    }

    public function edit(Request $request, Akun $pengguna): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.pengguna.edit', [
            'pengguna' => $pengguna->load(['customer', 'karyawan']),
            'daftarRole' => self::ROLES,
        ]);
    }

    public function update(Request $request, Akun $pengguna): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('akun', 'email')->ignore($pengguna->id)],
            'role' => ['required', 'in:admin,kurir,customer'],
            'password' => ['nullable', 'string', 'min:6'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'username' => ['nullable', 'string', 'max:30', Rule::unique('karyawan', 'username')->ignore($pengguna->karyawan?->id ?? null)],
        ], [
            'email.unique' => 'Email sudah digunakan.',
            'username.unique' => 'Username kurir sudah digunakan.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        if ($pengguna->id === $request->user()->id) {
            return redirect()
                ->route('admin.pengguna.index')
                ->with('error', 'Anda tidak dapat mengubah akun Anda sendiri.');
        }

        DB::transaction(function () use ($pengguna, $data) {
            $roleLama = $pengguna->role;

            $pengguna->nama = $data['nama'];
            $pengguna->email = $data['email'];
            $pengguna->no_hp = $data['no_hp'] ?? null;
            if (! empty($data['password'])) {
                $pengguna->password = $data['password'];
            }
            $pengguna->role = $data['role'];
            $pengguna->save();

            if ($roleLama !== $pengguna->role) {
                $this->sinkronkanTabelAnak($pengguna, $data['username'] ?? null, true);
            }
        });

        return redirect()
            ->route('admin.pengguna.index')
            ->with('status', 'Pengguna diperbarui.');
    }

    public function destroy(Request $request, Akun $pengguna): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($pengguna->id === $request->user()->id) {
            return redirect()
                ->route('admin.pengguna.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        DB::transaction(function () use ($pengguna) {
            Customer::query()->where('akun_id', $pengguna->id)->delete();
            Karyawan::query()->where('akun_id', $pengguna->id)->delete();
            $pengguna->delete();
        });

        return redirect()
            ->route('admin.pengguna.index')
            ->with('status', "Pengguna {$pengguna->nama} dihapus.");
    }

    private function sinkronkanTabelAnak(Akun $akun, ?string $username, bool $gantiRole = false): void
    {
        if ($akun->role === 'customer') {
            if ($gantiRole) {
                Karyawan::query()->where('akun_id', $akun->id)->delete();
            }

            if (! Customer::query()->where('akun_id', $akun->id)->exists()) {
                Customer::create(['akun_id' => $akun->id]);
            }

            return;
        }

        // admin & kurir -> baris karyawan (kurir wajib punya username)
        if ($gantiRole) {
            Customer::query()->where('akun_id', $akun->id)->delete();
        }

        if (! Karyawan::query()->where('akun_id', $akun->id)->exists()) {
            Karyawan::create([
                'akun_id' => $akun->id,
                'username' => $username ?: $akun->nama,
            ]);
        } elseif ($akun->role === 'kurir' && $username) {
            Karyawan::query()
                ->where('akun_id', $akun->id)
                ->update(['username' => $username]);
        }
    }

    private function roleValid(mixed $role): ?string
    {
        return in_array($role, self::ROLES, true) ? $role : null;
    }
}
