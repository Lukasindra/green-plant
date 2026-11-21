    <x-input nama="nama" label="Nama" wajib value="{{ old('nama', $pengguna->nama ?? '') }}" />

    <x-input nama="email" label="Email" tipe="email" wajib value="{{ old('email', $pengguna->email ?? '') }}" />

    <x-select nama="role" label="Role">
        @foreach ($daftarRole as $peran)
            <option value="{{ $peran }}" @selected(old('role', $pengguna->role ?? '') === $peran)>{{ ucfirst($peran) }}</option>
        @endforeach
    </x-select>

    <x-input nama="no_hp" label="No HP (opsional)" value="{{ old('no_hp', $pengguna->no_hp ?? '') }}" />

    <x-input nama="password" tipe="password" :wajib="! isset($pengguna)" :label="isset($pengguna) ? 'Password baru (kosongkan bila tidak diubah)' : 'Password'" />

    <x-input nama="username" label="Username (untuk kurir)" placeholder="Wajib bila role kurir" value="{{ old('username', $pengguna->karyawan?->username ?? '') }}" />
