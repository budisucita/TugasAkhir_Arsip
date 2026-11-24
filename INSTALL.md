# Panduan Instalasi Sistem Arsip Dharma Bhakti

## Persyaratan Sistem
- PHP ^8.2
- Composer
- Node.js & NPM
- MySQL Database

## Langkah Instalasi

1. **Persiapan Lingkungan**
   - Salin file konfigurasi: `cp .env.example .env`
   - Pastikan database MySQL tersedia (default: `simarsip`).
   - Ubah konfigurasi `.env` sesuai kebutuhan (DB_USERNAME, DB_PASSWORD, dll).

2. **Instalasi Dependensi**
   - PHP: `composer install`
   - JavaScript: `npm install`

3. **Konfigurasi Aplikasi**
   - Generate Key: `php artisan key:generate`
   - Link Storage: `php artisan storage:link`

4. **Database**
   - Jalankan migrasi: `php artisan migrate --force`
   - Jalankan seeder: `php artisan db:seed --force`

5. **Build Frontend**
   - `npm run build`

6. **Verifikasi**
   - Jalankan test: `php artisan test`
   - Akses aplikasi di browser.

## Catatan Tambahan
- Jika menggunakan Laragon, pastikan versi PHP yang aktif adalah 8.2+.
- Pastikan Node.js terdeteksi di terminal Anda.
