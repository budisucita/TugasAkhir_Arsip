<?php

namespace Tests\Feature;

use App\Filament\Resources\SuratResource;
use App\Models\Surat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji generator nomor surat otomatis berformat
 * [nomor_urut]/[kode_jenis_surat]/[kode_bulan]/[tahun].
 *
 * Fokus:
 * - Format baku (contoh: 001/ST-DB/XI/2026).
 * - Nomor urut melanjutkan nomor tertinggi pada bulan & tahun berjalan.
 * - Tidak menghasilkan duplikasi terhadap record yang sudah ada.
 */
class SuratNomorGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public function test_generate_nomor_surat_keluar_mengikuti_format_baku(): void
    {
        $romawi = self::BULAN_ROMAWI[(int) now()->month];

        $nomor = SuratResource::generateNomorSurat('keluar');

        $this->assertSame('001/ST-DB/' . $romawi . '/' . now()->year, $nomor);
    }

    public function test_generate_nomor_surat_masuk_menggunakan_kode_sm(): void
    {
        $nomor = SuratResource::generateNomorSurat('masuk');

        $this->assertStringStartsWith('001/SM/', $nomor);
    }

    public function test_generate_nomor_melanjutkan_urutan_terakhir_pada_bulan_yang_sama(): void
    {
        $romawi = self::BULAN_ROMAWI[(int) now()->month];
        $this->makeSurat('005/ST-DB/' . $romawi . '/' . now()->year);

        $nomor = SuratResource::generateNomorSurat('keluar');

        $this->assertSame('006/ST-DB/' . $romawi . '/' . now()->year, $nomor);
    }

    public function test_generate_nomor_menghindari_duplikasi_dengan_record_yang_ada(): void
    {
        $romawi = self::BULAN_ROMAWI[(int) now()->month];

        $pertama = SuratResource::generateNomorSurat('keluar');
        $this->makeSurat($pertama);

        $berikutnya = SuratResource::generateNomorSurat('keluar');

        $this->assertNotSame($pertama, $berikutnya);
        $this->assertSame('002/ST-DB/' . $romawi . '/' . now()->year, $berikutnya);
    }

    public function test_generate_nomor_mengabaikan_nomor_pada_bulan_lain(): void
    {
        $romawiLain = (int) now()->month === 1 ? 'XII' : 'I';
        $this->makeSurat('099/ST-DB/' . $romawiLain . '/' . now()->year);

        $romawi = self::BULAN_ROMAWI[(int) now()->month];
        $nomor = SuratResource::generateNomorSurat('keluar');

        $this->assertSame('001/ST-DB/' . $romawi . '/' . now()->year, $nomor);
    }

    private function makeSurat(string $noSurat): Surat
    {
        return Surat::create([
            'no_surat' => $noSurat,
            'pengirim' => 'Dinas Uji',
            'tanggal_terima' => now()->toDateString(),
            'status' => 'keluar',
            'perihal' => 'Perihal uji',
            'file_surat' => 'surat/file/uji.pdf',
        ]);
    }
}
