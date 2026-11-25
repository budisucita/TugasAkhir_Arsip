<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Arsip Dharma Bhakti

Sistem Informasi Manajemen Arsip untuk ST. Dharma Bhakti. Aplikasi ini dibangun menggunakan Laravel 11 dan Filament 3.

## 📋 Persyaratan Sistem

Sebelum memulai instalasi, pastikan perangkat Anda memenuhi persyaratan berikut:

### Perangkat Keras (Rekomendasi)
- **Processor**: Minimal Dual Core 2GHz+
- **RAM**: Minimal 4GB (8GB direkomendasikan untuk development lancar)
- **Disk Space**: Minimal 1GB free space

### Perangkat Lunak
- **OS**: Windows 10/11, macOS, atau Linux (Ubuntu/CentOS)
- **PHP**: Versi **8.2** atau lebih baru
- **Composer**: Dependency manager untuk PHP
- **Node.js**: Versi 18.x atau lebih baru (termasuk NPM)
- **Database**: MySQL 8.0+ atau MariaDB 10.x+
- **Web Server**: Apache/Nginx (atau menggunakan `php artisan serve` untuk development)

### Ekstensi PHP
Pastikan ekstensi PHP berikut aktif di `php.ini` Anda:
- `intl`
- `zip`
- `pdo_mysql`
- `fileinfo`

---

## 🚀 Panduan Instalasi

Ikuti langkah-langkah berikut untuk menginstal aplikasi di lingkungan lokal (local development).

### 1. Dapatkan Source Code
Clone repositori ini ke folder project Anda:
```bash
git clone https://github.com/username/repo-arsip.git
cd ARSIP
```

### 2. Instal Dependensi
Instal dependensi PHP menggunakan Composer dan dependensi frontend menggunakan NPM:

```bash
# Instal dependensi PHP
composer install

# Instal dependensi Node.js
npm install
```

> **Catatan**: Jika terjadi error pada composer install terkait database, Anda bisa menggunakan `composer install --no-scripts` terlebih dahulu, lalu jalankan script setup manual nanti.

### 3. Konfigurasi Environment
Salin file contoh konfigurasi `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan konfigurasi database Anda:

```ini
APP_NAME="Arsip Dharma Bhakti"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simarsip  # Sesuaikan dengan nama database Anda
DB_USERNAME=root      # Sesuaikan username database
DB_PASSWORD=          # Sesuaikan password database
```

### Setup Database & Key
Buat database kosong bernama `simarsip` (atau sesuai konfigurasi .env Anda) di MySQL, lalu jalankan perintah berikut:

```bash
# Generate Application Key
php artisan key:generate

# Migrasi Database dan Seed Data Awal
php artisan migrate --seed
```

### 5. Setup Storage
Buat symbolic link untuk folder storage agar file yang diupload bisa diakses publik:

```bash
php artisan storage:link
```

---

## 🖥️ Menjalankan Aplikasi

Untuk menjalankan aplikasi di lingkungan development, Anda perlu menjalankan dua terminal terpisah:

**Terminal 1 (Laravel Server):**
```bash
php artisan serve
```

**Terminal 2 (Vite Assets):**
```bash
npm run dev
```

Akses aplikasi melalui browser di: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Akun Demo (Default Seeder)
Gunakan kredensial berikut untuk login:

| Role | Email | Password |
|------|-------|----------|
| **Super Admin** | `admin@dharmabhakti.com` | `password` |
| **Pengurus** | `pengurus@dharmabhakti.com` | `password` |

---

## 🔧 Troubleshooting & Masalah Umum

**1. Permission Denied (Linux/macOS)**
Jika Anda mengalami error permission pada folder storage:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**2. Tampilan Berantakan / Style Hilang**
Hentikan `npm run dev`, lalu jalankan `npm run build` sekali saja. Hindari menjalankan dev server secara terus-menerus pada environment Windows (lihat bagian *Optimasi Performa Panel Admin*).

**3. Error Filament / Livewire**
Jika terjadi error pada panel admin, coba bersihkan cache:
```bash
php artisan optimize:clear
php artisan filament:optimize-clear
```

**4. Gambar tidak muncul**
Pastikan Anda sudah menjalankan `php artisan storage:link`. Jika masih bermasalah, hapus folder `public/storage` lalu jalankan perintah link ulang.

---

## 🗄️ Sinkronisasi Penyimpanan (Local ↔ S3)

Aplikasi menyediakan perintah artisan `storage:sync` untuk menyelaraskan berkas antara disk lokal (`public`) dan bucket S3 menggunakan verifikasi checksum MD5. Secara default perintah berjalan sebagai **dry-run** (tidak mengubah apa pun) sehingga aman untuk inspeksi.

```bash
# Lihat perbedaan saja (dry-run)
php artisan storage:sync --from=public --to=s3 --checksum

# Terapkan sinkronisasi dua arah
php artisan storage:sync --from=public --to=s3 --direction=both --checksum --apply

# Tarik berkas dari S3 ke lokal
php artisan storage:sync --from=s3 --to=public --direction=pull --apply
```

Opsi penting:
- `--direction=push|pull|both` — arah sinkronisasi (default `both`).
- `--conflict=skip|from|to|newest` — penanganan konflik bila path sama namun isi berbeda (default `skip`).
- `--checksum` — bandingkan checksum MD5, bukan hanya ukuran.
- `--prune` — hapus berkas berlebih di sisi penerima (gunakan dengan hati-hati).
- `--apply` — tanpa flag ini perintah hanya menampilkan rencana (dry-run).

---

## 🌐 Deployment (Production)

Untuk penggunaan di server production:
1.  Ubah `APP_ENV=production` dan `APP_DEBUG=false` di `.env`.
2.  Jalankan `composer run deploy` (mencakup `npm run build`, migrasi, cache Filament, dan optimasi cache Laravel).
3.  Pastikan **OPcache aktif** pada `php.ini` server (lihat bagian *Optimasi Performa Panel Admin*).
4.  Gunakan supervisor untuk menjalankan queue worker jika diperlukan.

---

## ⚡ Optimasi Performa Panel Admin (Windows / Laragon)

Panel admin Filament 3 yang memakai tema Vite kustom (`->viteTheme('resources/css/filament/admin/theme.css')`) berpotensi terasa berat pada environment Windows (NTFS) bila aset belum dibuild dan OPcache tidak aktif.

### Akar Masalah
1. **Pemindaian berkas vendor oleh Tailwind.** `resources/css/filament/admin/tailwind.config.js` mencantumkan `./vendor/filament/**/*.blade.php` pada `content`. Selama `npm run dev` berjalan, Vite + Tailwind JIT memantau **seluruh** berkas Blade vendor tersebut. Pada NTFS, globbing ribuan berkas ini sangat lambat dan dipicu ulang setiap ada perubahan, sehingga setiap interaksi menu terasa berat.
2. **Aset belum dibuild.** Tanpa `npm run build`, CSS tema disajikan lewat dev server (memerlukan `public/hot`) sehingga pemindaian berjalan terus-menerus pada setiap permintaan.
3. **Cache Blade Icons & komponen Filament belum dibuat.** Filament memindai ratusan ikon SVG dan komponen pada setiap permintaan.
4. **OPcache tidak aktif.** Setiap permintaan memaksa PHP mem-parse ulang ratusan berkas PHP dari disk NTFS.

### Langkah Optimasi
```bash
# 1. Hentikan dev server (agar tidak ada pemindaian berkas vendor).
#    Tekan Ctrl+C pada terminal `npm run dev` jika sedang berjalan.

# 2. Build aset produksi sekali saja.
npm run build

# 3. Aktifkan seluruh cache bawaan Filament (komponen + Blade Icons).
php artisan filament:optimize
```

4. **Aktifkan OPcache** pada `php.ini` PHP yang dipakai Laragon. Versi PHP aktif dapat dilihat di `C:\laragon\etc\apache2\mod_php.conf` (mis. `php-8.2.30`):
```ini
zend_extension=opcache
[opcache]
opcache.enable=1
```
   > **Penting:** Restart Laragon (Stop All → Start All) setelah mengubah `php.ini` agar OPcache aktif pada proses web server. Cek cepat via CLI: `php -r "var_dump(extension_loaded('Zend OPcache'));"`.

### Alur Deployment
Seluruh langkah di atas tersedia sebagai satu skrip gabungan:
```bash
composer run deploy
```
Skrip menjalankan secara berurutan: `npm run build` → `migrate --force` → `filament:optimize` → `optimize` (cache config/route/view).

### Verifikasi
Ukur TTFB halaman login panel admin (sesuaikan URL dengan virtual host Anda):
```powershell
$u = 'http://localhost/TugasAkhir_Arsip/public/admin/login'
1..6 | ForEach-Object { (Measure-Command { Invoke-WebRequest $u -UseBasicParsing | Out-Null }).TotalMilliseconds }
```
Panel dinyatakan teroptimasi bila:
- TTFB stabil pada permintaan hangat dan turun signifikan dibanding kondisi sebelum optimasi.
- Tidak ada proses `npm run dev` yang berjalan (`public/hot` tidak ada).
- Berkas cache tersedia: `public/build/manifest.json`, `bootstrap/cache/blade-icons.php`, dan `bootstrap/cache/filament/`.

### Optimasi Pengambilan Data `settings` (View Composer)
Sebelumnya `AppServiceProvider::boot()` menjalankan `Cache::rememberForever('settings', ...)` lalu `View::share('settings', ...)` pada **setiap** request — termasuk request internal Livewire dan panel admin Filament yang tidak membutuhkan `$settings`. Ini menambah beban cache/DB pada setiap interaksi admin.

Pengambilan data kini dipindah ke `View::composer` dan hanya dieksekusi ketika salah satu view publik berikut dirender:
```php
['layouts.public', 'welcome', 'agenda', 'agenda-show', 'galeri', 'pages.*']
```
Composer memanggil `$view->with('settings', $settings)`, sehingga `$settings` tetap tersedia di semua view publik (termasuk `@section('title', ...)` pada child view).

Dampak:
- Request panel admin Filament & aksi Livewire tidak lagi menyentuh cache maupun tabel `settings`.
- Logika cache tidak berubah: `rememberForever('settings')` untuk baca, dan `Cache::forget('settings')` di `PengaturanWebsite::save()` untuk invalidasi.

Hasil verifikasi (bootstrap app + query log):
```
Request /admin/login  -> settings composer hits = 0, settings DB queries = 0
Request /kontak       -> settings composer hits = 2, settings DB queries = 1 (cold cache)
```

### Tuning Tambahan (Opsional)
- Naikkan `opcache.max_accelerated_files` (mis. `20000`) bila jumlah berkas PHP proyek melebihi nilai default `10000`.
- Kecualikan folder `vendor`, `storage`, dan `public/build` dari pemindaian real-time Windows Defender untuk mengurangi beban I/O NTFS.

---
© 2024 - 2025 ST. Dharma Bhakti
