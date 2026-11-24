<?php

namespace App\Filament\Resources\KeuanganResource\Pages;

use App\Filament\Resources\KeuanganResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListKeuangans extends ListRecords
{
    protected static string $resource = KeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak_laporan')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->modalHeading('Cetak Laporan Keuangan')
                ->form([
                    Forms\Components\Select::make('kegiatan_id')
                        ->label('Pilih Kegiatan')
                        ->relationship('kegiatan', 'nama_kegiatan')
                        ->searchable()
                        ->preload()
                        ->placeholder('Semua Kegiatan'),
                    Forms\Components\Select::make('jenis_transaksi')
                        ->label('Jenis Transaksi')
                        ->options([
                            'pemasukan' => 'Pemasukan',
                            'pengeluaran' => 'Pengeluaran',
                        ])
                        ->placeholder('Semua Transaksi'),
                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Dari Tanggal'),
                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Sampai Tanggal'),
                 ])
                ->action(function (array $data) {
                    $url = route('laporan.keuangan.print') . '?' . http_build_query(array_filter($data));
                    $this->js('window.open("'.$url.'", "_blank")');
                }),
            Actions\CreateAction::make(),
        ];
    }
}
