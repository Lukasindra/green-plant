    <div>
        <label for="nama" class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
        <input type="text" id="nama" name="nama" value="{{ old('nama', $pengguna->nama ?? '') }}" required
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $pengguna->email ?? '') }}" required
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="role" class="mb-1 block text-sm font-medium text-gray-700">Role</label>
        <select id="role" name="role"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
            @foreach ($daftarRole as $peran)
                <option value="{{ $peran }}" @selected(old('role', $pengguna->role ?? '') === $peran)>{{ ucfirst($peran) }}</option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="no_hp" class="mb-1 block text-sm font-medium text-gray-700">No HP (opsional)</label>
        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $pengguna->no_hp ?? '') }}"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('no_hp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">
            {{ isset($pengguna) ? 'Password baru (kosongkan bila tidak diubah)' : 'Password' }}
        </label>
        <input type="password" id="password" name="password" @unless (isset($pengguna)) required @endunless
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username (untuk kurir)</label>
        <input type="text" id="username" name="username" value="{{ old('username', $pengguna->karyawan?->username ?? '') }}"
            placeholder="Wajib bila role kurir"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
        @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
