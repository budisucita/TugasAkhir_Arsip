<?php

namespace Tests\Feature;

use App\Filament\Resources\KeuanganResource\Pages\ListKeuangans;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\User;
use App\Support\KeuanganExcelImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Menguji fitur Impor Data Keuangan dari berkas Excel (.xlsx) pada panel admin.
 *
 * Cakupan pengujian:
 * - Pemetaan kolom berdasarkan NAMA header (bukan posisi), konversi tanggal
 *   serial Excel & string, normalisasi jenis, pembersihan nominal, dan
 *   penggabungan kolom keterangan.
 * - Pengabaian kolom di luar rentang header (termasuk kolom rumus bantuan).
 * - Penanganan baris kosong (dilewati tanpa menjadi kesalahan).
 * - Validasi ketat baris data yang tidak valid.
 * - Pencegahan duplikasi terhadap database maupun di dalam berkas.
 * - Otorisasi: tombol/aksi hanya untuk admin (pengurus ditolak dengan 403).
 * - Alur end-to-end aksi impor menyimpan data dengan kegiatan & user terkait.
 */
class KeuanganExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * Header kolom sesuai berkas referensi "Buku Kas".
     */
    private const HEADERS = [
        'Tanggal',
        'Jenis',
        'Kategori',
        'Nama / Sumber / Penerima',
        'Nominal (Rp)',
        'Catatan',
        'Link Bukti Foto',
    ];

    /**
     * Berkas sementara yang dibuat selama pengujian.
     *
     * @var array<int, string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pengurus(): User
    {
        return User::factory()->create(['role' => 'pengurus']);
    }

    private function importer(): KeuanganExcelImporter
    {
        return app(KeuanganExcelImporter::class);
    }

    /**
     * Membuat berkas .xlsx nyata untuk keperluan pengujian.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, mixed>|null  $headers
     */
    private function makeXlsx(array $rows, ?array $headers = null, string $sheetName = 'Buku Kas'): string
    {
        $headers ??= self::HEADERS;

        $base = tempnam(sys_get_temp_dir(), 'kgi');
        $path = $base . '.xlsx';
        @unlink($base);

        $this->tempFiles[] = $path;

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName($sheetName);
        $writer->addRow(Row::fromValues($headers));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    /**
     * Membungkus berkas .xlsx nyata menjadi berkas unggahan tiruan Livewire.
     */
    private function uploadedXlsx(string $path, string $name = 'impor.xlsx'): UploadedFile
    {
        return UploadedFile::fake()
            ->createWithContent($name, (string) file_get_contents($path))
            ->mimeType(self::XLSX_MIME);
    }

    // ---------------------------------------------------------------------
    // Pemetaan kolom & konversi nilai
    // ---------------------------------------------------------------------

    public function test_analisis_gagal_bila_header_tanggal_tidak_ditemukan(): void
    {
        $kegiatan = Kegiatan::factory()->create();
        $path = $this->makeXlsx([], ['Foo', 'Bar', 'Baz']);

        $result = $this->importer()->analyze($path, $kegiatan->id);

        $this->assertNotNull($result['fatal']);
        $this->assertSame([], $result['rows']);
    }

    public function test_analisis_memetakan_kolom_dan_mengonversi_nilai(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        $path = $this->makeXlsx([
            // Tanggal serial Excel (45678 = 2025-01-21), nominal teks berpemisah ribuan.
            [45678, 'Pemasukan', 'Donasi', 'Budi Santoso', '1.500.000', 'Iuran warga', 'https://drive.google.com/file/d/abc/view'],
            // Tanggal string (dd/mm/yyyy), jenis huruf besar, nominal angka, bukti kosong.
            ['05/03/2026', 'PENGELUARAN', 'Konsumsi', 'Warung Bu Tini', 250000, 'Rapat panitia', ''],
        ]);

        $result = $this->importer()->analyze($path, $kegiatan->id);

        $this->assertNull($result['fatal']);
        $this->assertCount(2, $result['rows']);

        $pemasukan = $result['rows'][0];
        $this->assertSame('2025-01-21', $pemasukan['tanggal']);
        $this->assertSame('pemasukan', $pemasukan['jenis_transaksi']);
        $this->assertSame(1500000.0, $pemasukan['jumlah']);
        $this->assertSame('[Donasi] Budi Santoso - Iuran warga', $pemasukan['keterangan']);
        $this->assertSame('https://drive.google.com/file/d/abc/view', $pemasukan['bukti_file']);

        $pengeluaran = $result['rows'][1];
        $this->assertSame('2026-03-05', $pengeluaran['tanggal']);
        $this->assertSame('pengeluaran', $pengeluaran['jenis_transaksi']);
        $this->assertSame(250000.0, $pengeluaran['jumlah']);
        $this->assertSame('[Konsumsi] Warung Bu Tini - Rapat panitia', $pengeluaran['keterangan']);
        $this->assertNull($pengeluaran['bukti_file']);

        $this->assertSame(1500000.0, $result['total_pemasukan']);
        $this->assertSame(250000.0, $result['total_pengeluaran']);
    }

    public function test_analisis_hanya_membaca_kolom_dalam_rentang_header(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        // 13 kolom (A–M); kolom M (index 12) berisi rumus bantuan yang harus diabaikan.
        $headers = [...self::HEADERS, 'Kolom8', 'Kolom9', 'Kolom10', 'Kolom11', 'Kolom12', 'Kolom13'];

        $path = $this->makeXlsx([
            [45678, 'Pemasukan', 'Donasi', 'Budi', 100000, 'Catatan', '', 999999, 999999, 999999, 999999, 999999, '=SUM(E:E)'],
        ], $headers);

        $result = $this->importer()->analyze($path, $kegiatan->id);

        $this->assertCount(1, $result['rows']);
        $this->assertSame(100000.0, $result['rows'][0]['jumlah']);
        $this->assertSame(100000.0, $result['total_pemasukan']);
    }

    // ---------------------------------------------------------------------
    // Baris kosong
    // ---------------------------------------------------------------------

    public function test_analisis_melewati_baris_kosong_tanpa_menghasilkan_kesalahan(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        $path = $this->makeXlsx([
            [45678, 'Pemasukan', 'Donasi', 'Budi', 100000, 'A', ''],
            ['', '', '', '', '', '', ''],
            [45679, 'Pemasukan', 'Donasi', 'Siti', 200000, 'B', ''],
        ]);

        $result = $this->importer()->analyze($path, $kegiatan->id);

        $this->assertSame([], $result['errors']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame(300000.0, $result['total_pemasukan']);
    }

    // ---------------------------------------------------------------------
    // Validasi baris tidak valid
    // ---------------------------------------------------------------------

    public function test_analisis_mencatat_baris_dengan_format_tidak_valid(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        $path = $this->makeXlsx([
            ['bukan-tanggal', 'transfer', 'Kategori', 'Nama', '0', 'Catatan ada', ''],
            [45678, 'pemasukan', '', '', 50000, '', ''],
        ]);

        $result = $this->importer()->analyze($path, $kegiatan->id);

        $this->assertCount(0, $result['rows']);
        $this->assertCount(2, $result['errors']);

        $this->assertStringContainsString('Tanggal tidak valid', $result['errors'][0]['reason']);
        $this->assertStringContainsString('Jenis transaksi', $result['errors'][0]['reason']);
        $this->assertStringContainsString('Nominal', $result['errors'][0]['reason']);

        $this->assertStringContainsString('Keterangan kosong', $result['errors'][1]['reason']);
    }

    // ---------------------------------------------------------------------
    // Anti-duplikasi
    // ---------------------------------------------------------------------

    public function test_analisis_melewati_duplikat_database_dan_dalam_berkas(): void
    {
        $kegiatan = Kegiatan::factory()->create();
        $admin = $this->admin();

        Keuangan::create([
            'kegiatan_id' => $kegiatan->id,
            'user_id' => $admin->id,
            'jenis_transaksi' => 'pemasukan',
            'jumlah' => 100000,
            'keterangan' => '[Donasi] Budi',
            'bukti_file' => null,
            'tanggal' => '2025-01-21',
        ]);

        $path = $this->makeXlsx([
            [45678, 'Pemasukan', 'Donasi', 'Budi', 100000, '', ''], // duplikat database
            [45679, 'Pemasukan', 'Donasi', 'Siti', 200000, '', ''], // baris baru
            [45679, 'Pemasukan', 'Donasi', 'Siti', 200000, '', ''], // duplikat di dalam berkas
        ]);

        $result = $this->importer()->analyze($path, $kegiatan->id);

        $this->assertCount(1, $result['rows']);
        $this->assertSame(200000.0, $result['total_pemasukan']);
        $this->assertCount(2, $result['skipped']);
    }

    // ---------------------------------------------------------------------
    // Penyimpanan (persist)
    // ---------------------------------------------------------------------

    public function test_persist_menyimpan_data_dengan_kegiatan_dan_user_id(): void
    {
        $kegiatan = Kegiatan::factory()->create();
        $admin = $this->admin();

        $rows = [
            [
                'line' => 2,
                'tanggal' => '2025-01-21',
                'jenis_transaksi' => 'pemasukan',
                'jumlah' => 1500000.0,
                'keterangan' => '[Donasi] Budi Santoso - Iuran warga',
                'bukti_file' => 'https://drive.google.com/file/d/abc/view',
            ],
            [
                'line' => 3,
                'tanggal' => '2026-03-05',
                'jenis_transaksi' => 'pengeluaran',
                'jumlah' => 250000.0,
                'keterangan' => '[Konsumsi] Warung Bu Tini - Rapat panitia',
                'bukti_file' => null,
            ],
        ];

        $saved = $this->importer()->persist($rows, $kegiatan->id, $admin->id);

        $this->assertSame(2, $saved);
        $this->assertDatabaseCount('keuangan', 2);

        $this->assertDatabaseHas('keuangan', [
            'kegiatan_id' => $kegiatan->id,
            'user_id' => $admin->id,
            'jenis_transaksi' => 'pemasukan',
            'keterangan' => '[Donasi] Budi Santoso - Iuran warga',
            'bukti_file' => 'https://drive.google.com/file/d/abc/view',
        ]);

        $pengeluaran = Keuangan::query()->where('jenis_transaksi', 'pengeluaran')->firstOrFail();
        $this->assertSame('2026-03-05', $pengeluaran->tanggal->format('Y-m-d'));
        $this->assertNull($pengeluaran->bukti_file);
        $this->assertSame($admin->id, $pengeluaran->user_id);
    }

    // ---------------------------------------------------------------------
    // Otorisasi (hak akses)
    // ---------------------------------------------------------------------

    public function test_aksi_impor_hanya_terlihat_oleh_admin(): void
    {
        Kegiatan::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(ListKeuangans::class)
            ->assertActionVisible('impor_excel');

        Livewire::actingAs($this->pengurus())
            ->test(ListKeuangans::class)
            ->assertActionHidden('impor_excel');
    }

    public function test_pengurus_ditolak_menjalankan_aksi_impor(): void
    {
        Kegiatan::factory()->create();

        $pengurus = $this->pengurus();
        $this->actingAs($pengurus);

        // Kebijakan KeuanganPolicy::create() hanya mengizinkan admin.
        $this->assertFalse(
            Gate::forUser($pengurus)->allows('create', Keuangan::class),
            'Pengurus seharusnya tidak diizinkan membuat data keuangan.'
        );

        // Bagi pengurus, aksi "Impor Excel" tersembunyi — dan karena Filament
        // menonaktifkan aksi yang tersembunyi, aksi ini tidak dapat dimount
        // maupun dieksekusi (bukan sekadar disembunyikan dari tampilan).
        Livewire::actingAs($pengurus)
            ->test(ListKeuangans::class)
            ->assertActionHidden('impor_excel')
            ->assertActionDisabled('impor_excel');

        // Entry point pembuatan data keuangan (yang dipakai oleh proses impor)
        // menolak pengurus dengan kode 403.
        $this->get('/admin/keuangans/create')->assertForbidden();

        $this->assertDatabaseCount('keuangan', 0);
    }

    public function test_admin_dapat_menjalankan_aksi_impor(): void
    {
        Kegiatan::factory()->create();

        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(ListKeuangans::class)
            ->assertActionVisible('impor_excel')
            ->assertActionEnabled('impor_excel');
    }

    // ---------------------------------------------------------------------
    // Alur end-to-end aksi impor (admin)
    // ---------------------------------------------------------------------

    public function test_admin_dapat_mengimpor_data_keuangan_dari_excel(): void
    {
        $admin = $this->admin();
        $kegiatan = Kegiatan::factory()->create(['nama_kegiatan' => 'Ogoh-Ogoh 2026']);

        $path = $this->makeXlsx([
            [45678, 'Pemasukan', 'Donasi', 'Budi Santoso', 1500000, 'Iuran warga', 'https://drive.google.com/file/d/abc/view'],
            [45679, 'Pengeluaran', 'Konsumsi', 'Warung Bu Tini', 250000, 'Rapat panitia', ''],
        ]);

        $this->actingAs($admin);
        Livewire::actingAs($admin);

        Livewire::test(ListKeuangans::class)
            ->callAction('impor_excel', [
                'kegiatan_id' => $kegiatan->id,
                'file' => $this->uploadedXlsx($path),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseCount('keuangan', 2);

        $this->assertDatabaseHas('keuangan', [
            'kegiatan_id' => $kegiatan->id,
            'user_id' => $admin->id,
            'jenis_transaksi' => 'pemasukan',
            'keterangan' => '[Donasi] Budi Santoso - Iuran warga',
        ]);

        $this->assertDatabaseHas('keuangan', [
            'kegiatan_id' => $kegiatan->id,
            'user_id' => $admin->id,
            'jenis_transaksi' => 'pengeluaran',
            'keterangan' => '[Konsumsi] Warung Bu Tini - Rapat panitia',
        ]);
    }
}
