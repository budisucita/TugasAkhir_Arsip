<?php

namespace Tests\Feature;

use App\Filament\Widgets\KeuanganTrendChart;
use App\Models\Keuangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Menguji perbaikan konfigurasi zona waktu/locale serta kompatibilitas lintas-driver
 * pada widget grafik tren keuangan.
 *
 * Skenario yang diverifikasi:
 * - .env.example menetapkan zona waktu WITA (Asia/Makassar) dan locale Indonesia (id).
 * - Widget KeuanganTrendChart berjalan pada database SQLite (environment pengujian)
 *   tanpa error, mengagregasi pemasukan/pengeluaran per bulan dengan format periode
 *   'Y-m' yang konsisten dengan kunci Carbon.
 * - Bulan tanpa transaksi tetap menghasilkan nilai 0 (konsisten 12 bulan berurutan).
 */
class TimezoneAndKeuanganChartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membaca isi berkas contoh konfigurasi environment.
     */
    private function envExample(): string
    {
        return (string) file_get_contents(base_path('.env.example'));
    }

    /**
     * Memanggil getData() (protected) pada widget grafik via refleksi.
     */
    private function chartData(): array
    {
        $widget = new KeuanganTrendChart();
        $method = new \ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    private function makeKeuangan(string $tanggal, string $jenis, int $jumlah): Keuangan
    {
        return Keuangan::create([
            'jenis_transaksi' => $jenis,
            'jumlah' => $jumlah,
            'keterangan' => 'Uji '.$jenis,
            'tanggal' => $tanggal,
        ]);
    }

    public function test_env_example_menetapkan_zona_waktu_wita(): void
    {
        // Pencocokan baris persis (^...$) agar tidak tertipu oleh baris komentar.
        // \r? menoleransi akhir baris CRLF pada environment Windows.
        $this->assertMatchesRegularExpression(
            '/^APP_TIMEZONE=Asia\/Makassar\r?$/m',
            $this->envExample(),
        );
    }

    public function test_env_example_menetapkan_locale_indonesia(): void
    {
        $this->assertMatchesRegularExpression(
            '/^APP_LOCALE=id\r?$/m',
            $this->envExample(),
        );
    }

    public function test_grafik_berjalan_pada_sqlite_dan_mengagregasi_per_bulan(): void
    {
        // Bekukan waktu agar indeks bulan pada grafik bersifat deterministik.
        $this->travelTo(Carbon::create(2026, 6, 15, 10, 0, 0));

        $this->makeKeuangan('2026-06-10', 'pemasukan', 1000);   // bulan berjalan
        $this->makeKeuangan('2026-06-20', 'pemasukan', 500);    // bulan berjalan (akumulasi)
        $this->makeKeuangan('2026-05-05', 'pengeluaran', 300);  // bulan lalu
        $this->makeKeuangan('2026-01-12', 'pemasukan', 200);    // 5 bulan lalu

        $data = $this->chartData();

        $pemasukan = $data['datasets'][0]['data'];
        $pengeluaran = $data['datasets'][1]['data'];

        // Selalu 12 titik data (12 bulan terakhir).
        $this->assertCount(12, $data['labels']);
        $this->assertCount(12, $pemasukan);
        $this->assertCount(12, $pengeluaran);

        // Indeks 11 = bulan berjalan (Juni 2026): 1000 + 500.
        $this->assertSame(1500.0, $pemasukan[11]);
        // Indeks 10 = Mei 2026: pengeluaran 300.
        $this->assertSame(300.0, $pengeluaran[10]);
        // Indeks 6 = Januari 2026: pemasukan 200.
        $this->assertSame(200.0, $pemasukan[6]);

        // Label bulan berjalan mengikuti locale Indonesia yang dipaksakan widget.
        $this->assertSame('Jun 2026', $data['labels'][11]);
    }

    public function test_bulan_tanpa_transaksi_menghasilkan_nol(): void
    {
        $this->travelTo(Carbon::create(2026, 6, 15, 10, 0, 0));

        // Hanya 1 transaksi; 11 bulan lainnya harus bernilai 0.
        $this->makeKeuangan('2026-06-10', 'pemasukan', 1000);

        $data = $this->chartData();
        $pemasukan = $data['datasets'][0]['data'];
        $pengeluaran = $data['datasets'][1]['data'];

        // Bulan tertua (Juli 2025) tidak memiliki transaksi.
        $this->assertSame(0.0, $pemasukan[0]);
        $this->assertSame(0.0, $pengeluaran[0]);

        // Jumlah bulan bernilai > 0 tepat satu pada tiap dataset.
        $this->assertCount(1, array_filter($pemasukan, fn ($v) => $v > 0));
        $this->assertCount(0, array_filter($pengeluaran, fn ($v) => $v > 0));
    }
}
