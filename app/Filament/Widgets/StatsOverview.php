<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AnggotaResource;
use App\Filament\Resources\KegiatanResource;
use App\Filament\Resources\KeuanganResource;
use App\Filament\Resources\SuratResource;
use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Surat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Kunci cache hasil perhitungan statistik Dashboard.
     * Digunakan juga oleh KeuanganObserver untuk invalidasi saat data berubah.
     */
    public const CACHE_KEY = 'stats_overview';

    /**
     * Durasi cache (detik). Singkat agar data tetap akurat, namun cukup
     * untuk menekan query berulang pada setiap pemuatan Dashboard.
     */
    protected const CACHE_TTL = 60;

    protected function getStats(): array
    {
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            // Hitung total anggota
            $totalAnggota = Anggota::count();

            // Saldo (Pemasukan - Pengeluaran) dihitung dari SATU query agregasi
            // (selectRaw) alih-alih dua query sum() terpisah.
            $agregat = Keuangan::query()
                ->selectRaw("SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as pemasukan")
                ->selectRaw("SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as pengeluaran")
                ->first();

            $pemasukan = (int) ($agregat->pemasukan ?? 0);
            $pengeluaran = (int) ($agregat->pengeluaran ?? 0);
            $saldo = $pemasukan - $pengeluaran;

            // Hitung kegiatan aktif: hanya status 'Direncanakan' dan 'Berlangsung'.
            // Status 'Dibatalkan' dan 'Selesai' tidak dihitung sebagai kegiatan aktif.
            $kegiatanCount = Kegiatan::whereIn('status', ['Direncanakan', 'Berlangsung'])->count();

            // Hitung total surat
            $suratCount = Surat::count();

            return compact('totalAnggota', 'saldo', 'kegiatanCount', 'suratCount');
        });

        return [
            Stat::make('Total Anggota', $data['totalAnggota'])
                ->description('Anggota terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url(AnggotaResource::getUrl()),
            Stat::make('Saldo Keuangan', 'Rp ' . number_format($data['saldo'], 0, ',', '.'))
                ->description('Total Saldo saat ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($data['saldo'] >= 0 ? 'success' : 'danger')
                ->url(KeuanganResource::getUrl()),
            Stat::make('Kegiatan Aktif', $data['kegiatanCount'])
                ->description('Direncanakan / Berlangsung')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning')
                ->url(KegiatanResource::getUrl()),
            Stat::make('Total Surat', $data['suratCount'])
                ->description('Masuk & Keluar')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url(SuratResource::getUrl()),
        ];
    }
}
