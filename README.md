# Sistem Absensi Terpadu – SMA 4 Karawang

Laravel 11/12 · PHP 8.2+ · MySQL 8 · Blade + CSS kustom (tanpa build) · Alpine.js & Chart.js via CDN · dompdf.

Paket ini adalah **overlay**: berisi hanya file aplikasi (app, config, database, routes, resources, lang, public/css),
yang ditimpakan ke instalasi Laravel baru.

## Instalasi

```bash
composer create-project laravel/laravel absensi-sman4
cd absensi-sman4
composer require barryvdh/laravel-dompdf

# salin isi paket ini ke root proyek (timpa file yang sama)
cp -r /path/ke/paket/* .

# atur .env sesuai ENV-SNIPPET.txt (MySQL, timezone, locale), lalu:
php artisan key:generate        # bila belum
php artisan migrate --seed
php artisan serve
```

File Laravel bawaan yang **ditimpa**: `app/Models/User.php`, `app/Http/Controllers/Controller.php`,
`app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `routes/web.php`, `routes/console.php`,
`database/migrations/0001_01_01_000000_create_users_table.php`, `database/seeders/DatabaseSeeder.php`.
Bila proyek Anda sudah punya perubahan di file-file itu, gabungkan manual.

## Akun contoh (seeder, password semua: `password`)

| Role | Username |
|---|---|
| Admin | `admin` |
| Kesiswaan | `kesiswaan1`, `kesiswaan2` |
| Guru | `guru1` … `guru7` (guru1–6 wali kelas X-1…XII-2) |

Seeder membuat 1 tahun ajaran aktif, 6 kelas, 10–15 siswa/kelas, relasi wali + mapel, dan data absensi
7 hari efektif terakhir (termasuk `pending`, `ditolak`, `tidak_diabsen`, alpa otomatis). Hari ini sengaja kosong
agar bisa dicoba langsung. **Jangan jalankan seeder di produksi.**

## Scheduler (alpa otomatis & kelas tidak diabsen)

Command `absensi:tutup-hari` dijadwalkan setiap hari 23:59 (Asia/Jakarta). Di server produksi tambahkan cron:

```
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

Uji manual: `php artisan absensi:tutup-hari` atau `php artisan absensi:tutup-hari --tanggal=2026-10-07` (idempoten).

## Konfigurasi

`config/absensi.php`: `libur_mingguan` (default `[6, 7]`), `jam_alpa_otomatis`, nama/alamat sekolah (kop PDF), batas ukuran bukti.

## Peta aturan bisnis → kode

| Aturan | Lokasi |
|---|---|
| R-02, R-42 hari libur / 7 hari efektif | `App\Support\Kalender`, `DashboardService` |
| R-10–R-15 absen guru | `AbsensiGuruService`, `AbsenMandiriRequest` |
| R-14, R-25 scheduler | `TutupHariService`, `Console\Commands\TutupHari` |
| R-20–R-26 absen siswa, konkurensi | `AbsensiKelasService` (unique constraint + transaksi) |
| R-30–R-36 validasi/tolak/koreksi | `AbsensiGuruService`, `AbsensiKelasService`, `Validasi\*Controller` |
| R-40/R-41 angka dashboard vs rekap | `DashboardService` vs `RekapService` |
| R-50–R-54 tahun ajaran | `TahunAjaranService`, `Kelas::scopeTahunAktif` |
| R-60–R-63 relasi, `wali_key` | `RelasiService`, migrasi master |
| R-70–R-72 penghapusan | `KelasController@destroy`, soft delete |
| Hak akses | `RoleMiddleware`, `Policies\*`, `Form Request` |

## Catatan desain

- Detail laporan kelas, **Koreksi**, dan **Input Manual** berupa halaman terpisah (bukan modal); Tolak & Koreksi absen guru memakai modal.
- Perbaikan data `ditolak` untuk guru: dari dashboard / banner halaman Absen Mandiri & Absen Siswa (tanggal lampau diperbolehkan).
- Bukti izin/sakit di `storage/app/private/bukti`, dibuka lewat `/bukti/guru/{id}` (Policy: pemilik, kesiswaan, admin).
- Rekap guru menampilkan kelas yang pernah diampu (termasuk tahun ajaran lama) agar riwayat tetap bisa dicetak.
- Pembersihan: UI memakai CDN (Alpine, Chart.js, Google Fonts); untuk jaringan sekolah tertutup, unduh dan letakkan di `public/`.

## Status pengujian

Kode ditulis tanpa dapat dijalankan di lingkungan pembuatnya (tidak ada PHP/Composer). **Belum pernah dieksekusi.**
Jalankan `php artisan migrate:fresh --seed`, lalu telusuri alur: login tiap role → absen mandiri → absen siswa →
validasi/tolak → perbaikan → rekap/PDF/CSV, dan `php artisan absensi:tutup-hari`.
