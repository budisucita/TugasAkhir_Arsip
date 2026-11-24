<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LaporanKeuanganController;

Route::get('/', [LandingPageController::class, 'index'])->name('welcome');
Route::get('/galeri', [LandingPageController::class, 'galeri'])->name('galeri.index');
Route::get('/agenda', [LandingPageController::class, 'agenda'])->name('agenda.index');
Route::get('/agenda/{id}', [LandingPageController::class, 'agendaDetail'])->name('agenda.show');

Route::middleware('auth')->group(function () {
    Route::get('/laporan/keuangan/print', [LaporanKeuanganController::class, 'print'])->name('laporan.keuangan.print');
});

