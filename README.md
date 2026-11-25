# Green Plant

Toko online tanaman hias dan jasa taman: katalog, keranjang, checkout, pembayaran
Midtrans Snap, pelacakan pesanan, dashboard admin, dan tugas kurir.

## Stack

- PHP 8.3 / Laravel 13
- PostgreSQL (produksi & lokal); test memakai SQLite in-memory
- Vite + Tailwind CSS
- Midtrans Snap (sandbox/production)
- PHPUnit

## Setup Lokal

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

Isi koneksi database di `.env` (`DB_*`), lalu:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run dev
php artisan serve
```

Buka `http://localhost:8000`.

### Akun demo (hasil seeder)

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@greenplant.test` | `password` |
| Kurir | `kurir@greenplant.test` | `password` |
| Customer | `customer@greenplant.test` | `password` |

Ganti `SEED_PASSWORD` dan email demo di `.env` sebelum deploy. Seeder aman
dijalankan berulang (`updateOrCreate`) dan mengisi kategori, contoh produk/jasa,
serta ongkir awal.

## Midtrans

Isi kredensial di `.env`:

```env
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_NOTIFICATION_URL=
```

Atur URL notifikasi di dashboard Midtrans ke:

```text
https://domain-anda/midtrans/notifikasi
```

Saat pengembangan lokal, gunakan tunnel (mis. ngrok) dan isi
`MIDTRANS_NOTIFICATION_URL`.

## Scheduler

Pembayaran dan pembatalan otomatis membutuhkan scheduler:

```bash
php artisan schedule:work        # lokal
```

Di server, tambahkan cron:

```cron
* * * * * cd /path/green-plant && php artisan schedule:run >> /dev/null 2>&1
```

Yang dijadwalkan:

- `pesanan:sinkronkan-pembayaran` — rekonsiliasi status Midtrans tiap 10 menit.
- `pesanan:batalkan-kedaluwarsa` — membatalkan pesanan belum dibayar setiap 01:00.

## Fitur Utama

- Katalog produk & jasa dengan pencarian case-insensitive (termasuk kategori).
- Keranjang berbasis session, checkout dengan reservasi stok 24 jam (anti-overselling).
- Pembayaran Midtrans Snap: webhook bertanda tangan, polling, bayar ulang, rekonsiliasi.
- Stok fisik dipotong setelah lunas; jika stok tidak cukup, pesanan ditandai
  `perlu_tindakan` dan muncul di notifikasi admin.
- Riwayat status pesanan (timeline) untuk customer dan admin.
- Invoice yang bisa dicetak/disimpan sebagai PDF.
- Email transaksional: pesanan dibuat, lunas, dikirim, dibatalkan.
- Profil customer (nama, HP, alamat, ganti password) dan lupa/reset password.
- Admin: produk, jasa, kategori, pesanan, pembayaran, pengiriman, laporan,
  pengguna, pengaturan ongkir.
- Kurir: daftar tugas antar dan konfirmasi selesai.

## Testing

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

Test memakai SQLite in-memory dan tidak memanggil jaringan Midtrans.

## Struktur Penting

- `app/Services/PembayaranService.php` — satu pintu perubahan status pembayaran.
- `app/Services/ReservasiStokService.php` — reservasi stok saat checkout.
- `app/Support/CatatanPesanan.php` — pencatat riwayat pesanan.
- `app/Support/PencarianKatalog.php` — pencarian katalog lintas database.
- `database/migrations/2026_11_18_000000_buat_tabel_inti.php` — skema dasar idempotent.

## Lisensi

Aplikasi internal Green Plant. Framework Laravel memakai lisensi MIT.
