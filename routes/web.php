<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LaporanKeuanganController;

Route::get('/', [LandingPageController::class, 'index'])->name('welcome');
Route::get('/galeri', [LandingPageController::class, 'galeri'])->name('galeri.index');
Route::get('/agenda', [LandingPageController::class, 'agenda'])->name('agenda.index');
Route::get('/agenda/{id}', [LandingPageController::class, 'agendaDetail'])->name('agenda.show');

// Halaman statis publik
Route::get('/privasi', [LandingPageController::class, 'privacy'])->name('privacy');
Route::get('/syarat-ketentuan', [LandingPageController::class, 'terms'])->name('terms');
Route::get('/kontak', [LandingPageController::class, 'contact'])->name('contact');

Route::middleware('auth')->group(function () {
    Route::get('/laporan/keuangan/print', [LaporanKeuanganController::class, 'print'])->name('laporan.keuangan.print');
});

