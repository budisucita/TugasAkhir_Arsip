<?php

namespace App\Filament\Widgets;

use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Surat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Hitung total anggota
        $totalAnggota = Anggota::count();
        
        // Hitung saldo (Pemasukan - Pengeluaran)
        $pemasukan = Keuangan::where('jenis_transaksi', 'pemasukan')->sum('jumlah');
        $pengeluaran = Keuangan::where('jenis_transaksi', 'pengeluaran')->sum('jumlah');
        $saldo = $pemasukan - $pengeluaran;

        // Hitung kegiatan yang akan datang (status != Selesai)
        $kegiatanCount = Kegiatan::where('status', '!=', 'Selesai')->count();
        
        // Hitung total surat
        $suratCount = Surat::count();

        return [
            Stat::make('Total Anggota', $totalAnggota)
                ->description('Anggota terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Saldo Keuangan', 'Rp ' . number_format($saldo, 0, ',', '.'))
                ->description('Total Saldo saat ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldo >= 0 ? 'success' : 'danger'),
            Stat::make('Kegiatan Aktif', $kegiatanCount)
                ->description('Direncanakan / Berlangsung')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
            Stat::make('Total Surat', $suratCount)
                ->description('Masuk & Keluar')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
