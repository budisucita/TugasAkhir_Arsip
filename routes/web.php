<?php

use Illuminate\Support\Facades\Route;
use App\Models\Keuangan;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\SecureFileController;

Route::get('/', [LandingPageController::class, 'index'])->name('welcome');
Route::get('/galeri', [LandingPageController::class, 'galeri'])->name('galeri.index');
Route::get('/agenda', [LandingPageController::class, 'agenda'])->name('agenda.index');
Route::get('/agenda/{id}', [LandingPageController::class, 'agendaDetail'])->name('agenda.show');

// Halaman statis publik
Route::get('/privasi', [LandingPageController::class, 'privacy'])->name('privacy');
Route::get('/syarat-ketentuan', [LandingPageController::class, 'terms'])->name('terms');
Route::get('/kontak', [LandingPageController::class, 'contact'])->name('contact');

Route::middleware('auth')->group(function () {
    // Cetak laporan keuangan: auth + Policy (hanya admin/pengurus yang berizin).
    Route::get('/laporan/keuangan/print', [LaporanKeuanganController::class, 'print'])
        ->can('viewAny', Keuangan::class)
        ->name('laporan.keuangan.print');

    // Ekspor laporan keuangan (.xlsx): auth + Policy yang sama seperti cetak.
    Route::get('/laporan/keuangan/export', [LaporanKeuanganController::class, 'exportExcel'])
        ->can('viewAny', Keuangan::class)
        ->name('laporan.keuangan.export');

    // Penyajian berkas sensitif dari disk privat (auth + Policy).
    Route::get('/secure-file/{type}/{id}/{field}', [SecureFileController::class, 'show'])
        ->whereNumber('id')
        ->name('secure-file.show');

    // Proxy unduhan berkas privat oleh admin (auth + Policy).
    Route::get('/admin/dokumen/{tipe}/{id}', [SecureFileController::class, 'download'])
        ->whereNumber('id')
        ->name('admin.dokumen.download');
});

