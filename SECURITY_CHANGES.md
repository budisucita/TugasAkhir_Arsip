# Dokumentasi Perubahan Keamanan Akses

**Tanggal Pembaruan:** 2026-01-19
**Perihal:** Penghapusan Filament Shield & Permission

## Ringkasan Perubahan
Per tanggal 18 Januari 2026, sistem akses kontrol telah disederhanakan dengan menghapus dependensi pada paket `bezhansalleh/filament-shield`. Sistem kini menggunakan Role-Based Access Control (RBAC) sederhana yang terintegrasi langsung pada model `User`.

## Detail Implementasi

### 1. Struktur Role
Akses dikendalikan oleh kolom `role` pada tabel `users` yang memiliki dua nilai valid:
- **admin**: Memiliki akses penuh (Super Admin).
- **pengurus**: Memiliki akses terbatas (Read-only / View-only).

### 2. Penghapusan Komponen Shield
Komponen berikut telah dihapus dari sistem:
- UI Filament Shield (Menu Roles & Permissions).
- Resource: `PermissionResource`.
- Policies: `RolePolicy`, `PermissionPolicy`.
- Observers: `PermissionObserver`.
- Commands: `FixPermissions`.
- Konfigurasi: `config/filament-shield.php`.

### 3. Kebijakan Akses (Policies)
Semua Policy telah diperbarui untuk memeriksa kolom `role` secara langsung:
- **Admin**: `create`, `update`, `delete`, `restore`, `forceDelete` -> `true`.
- **Pengurus**: `viewAny`, `view` -> `true`; operasi tulis -> `false`.

## Dampak
- Sidebar admin lebih bersih (tanpa menu teknis Shield).
- Performa lebih ringan karena tidak ada pengecekan permission database yang kompleks.
- Manajemen user lebih sederhana, cukup set role saat create/edit user.

## Referensi Historis
Dokumen ini menggantikan catatan sebelumnya tertanggal 2026-01-05 mengenai pembatasan akses Shield.
