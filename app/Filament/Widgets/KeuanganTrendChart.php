<?php

namespace App\Filament\Widgets;

use App\Models\Keuangan;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class KeuanganTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Keuangan Bulanan';

    protected static ?string $description = 'Perbandingan pemasukan dan pengeluaran 12 bulan terakhir';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = '30s';

    protected function getOptions(): array | RawJs | null
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        // Ringkas nilai sumbu Y agar mudah dibaca (rb / jt).
                        'callback' => RawJs::make(<<<'JS'
                            function (value) {
                                if (Math.abs(value) >= 1000000) {
                                    return (value / 1000000).toLocaleString('id-ID') + ' jt';
                                }
                                if (Math.abs(value) >= 1000) {
                                    return (value / 1000).toLocaleString('id-ID') + ' rb';
                                }
                                return value;
                            }
                        JS),
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        // Tampilkan nominal penuh dalam Rupiah saat kursor diarahkan.
                        'label' => RawJs::make(<<<'JS'
                            function (context) {
                                return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        JS),
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->subMonths(11)->startOfMonth();

        // Format periode 'YYYY-MM' berbeda per driver database: SQLite memakai
        // strftime(), sedangkan MySQL/MariaDB memakai DATE_FORMAT(). Ekspresi
        // dipilih sesuai driver aktif agar grafik tetap berjalan pada environment
        // pengujian (SQLite) maupun produksi (MySQL).
        $periodeSql = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', tanggal)",
            default => "DATE_FORMAT(tanggal, '%Y-%m')",
        };

        $rows = Keuangan::query()
            ->selectRaw("{$periodeSql} as periode")
            ->selectRaw("SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as pemasukan")
            ->selectRaw("SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as pengeluaran")
            ->whereNotNull('tanggal')
            ->where('tanggal', '>=', $start)
            ->groupBy('periode')
            ->orderBy('periode')
            ->get()
            ->keyBy('periode');

        $labels = [];
        $pemasukan = [];
        $pengeluaran = [];

        // Susun 12 bulan terakhir secara berurutan, termasuk bulan tanpa transaksi (nilai 0).
        for ($i = 11; $i >= 0; $i--) {
            $bulan = now()->subMonths($i)->locale('id');
            $row = $rows->get($bulan->format('Y-m'));

            $labels[] = $bulan->translatedFormat('M Y');
            $pemasukan[] = (float) ($row->pemasukan ?? 0);
            $pengeluaran[] = (float) ($row->pengeluaran ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $pemasukan,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.75)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $pengeluaran,
                    'backgroundColor' => 'rgba(244, 63, 94, 0.75)',
                    'borderColor' => 'rgb(244, 63, 94)',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
