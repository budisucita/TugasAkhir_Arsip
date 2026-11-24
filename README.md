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
Pastikan `npm run dev` sedang berjalan, atau jika di production, jalankan `npm run build`.

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
2.  Jalankan `npm run build` untuk membuild aset.
3.  Pastikan menggunakan `php artisan config:cache`, `route:cache`, dan `view:cache` untuk performa.
4.  Gunakan supervisor untuk menjalankan queue worker jika diperlukan.

---
© 2024 - 2025 ST. Dharma Bhakti
