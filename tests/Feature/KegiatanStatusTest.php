<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverview;
use App\Models\Kegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Menguji perlakuan status kegiatan "Dibatalkan" pada Dashboard dan kalender publik.
 *
 * Skenario yang diverifikasi:
 * - Kegiatan "Dibatalkan" TIDAK dihitung sebagai "Kegiatan Aktif" di Dashboard.
 * - "Kegiatan Aktif" hanya menjumlahkan status "Direncanakan" dan "Berlangsung".
 * - Kegiatan "Dibatalkan" tidak ditampilkan pada daftar agenda publik.
 * - Kegiatan "Direncanakan" dan "Berlangsung" tetap tampil dan dihitung dengan benar.
 */
class KegiatanStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membaca nilai stat "Kegiatan Aktif" dari widget StatsOverview.
     *
     * Cache dikosongkan lebih dahulu agar nilai yang dibaca selalu segar
     * dan tidak menyajikan hasil perhitungan dari skenario sebelumnya.
     */
    private function kegiatanAktifCount(): int
    {
        Cache::flush();

        $widget = new StatsOverview();
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        foreach ($method->invoke($widget) as $stat) {
            if ($stat->getLabel() === 'Kegiatan Aktif') {
                return (int) $stat->getValue();
            }
        }

        $this->fail('Stat "Kegiatan Aktif" tidak ditemukan pada widget StatsOverview.');
    }

    public function test_kegiatan_dibatalkan_tidak_dihitung_sebagai_kegiatan_aktif(): void
    {
        Kegiatan::factory()->create(['status' => 'Direncanakan']);
        Kegiatan::factory()->create(['status' => 'Berlangsung']);
        Kegiatan::factory()->create(['status' => 'Selesai']);
        Kegiatan::factory()->create(['status' => 'Dibatalkan']);

        // Hanya "Direncanakan" + "Berlangsung" yang dihitung (2), bukan 3/4.
        $this->assertSame(2, $this->kegiatanAktifCount());
    }

    public function test_dibatalkan_saja_menghasilkan_nol_kegiatan_aktif(): void
    {
        Kegiatan::factory()->create(['status' => 'Dibatalkan']);

        $this->assertSame(0, $this->kegiatanAktifCount());
    }

    public function test_kegiatan_dibatalkan_tidak_tampil_di_daftar_agenda_publik(): void
    {
        Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan Dibatalkan Uji',
            'status' => 'Dibatalkan',
        ]);

        $html = $this->get('/agenda')->assertOk()->getContent();

        // Kegiatan Dibatalkan bersifat internal: tidak muncul di grid maupun kalender.
        $this->assertStringNotContainsString('Kegiatan Dibatalkan Uji', $html);
        $this->assertSame(0, substr_count($html, 'Dibatalkan'));
    }

    public function test_detail_kegiatan_dibatalkan_tidak_dapat_diakses_publik(): void
    {
        $dibatalkan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan Dibatalkan Detail',
            'status' => 'Dibatalkan',
        ]);

        // Detail kegiatan berstatus Dibatalkan mengembalikan 404 bagi publik.
        $this->get(route('agenda.show', $dibatalkan->id))->assertNotFound();
    }

    public function test_kegiatan_direncanakan_dan_berlangsung_tetap_tampil_dengan_status_benar(): void
    {
        Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan Direncanakan Uji',
            'status' => 'Direncanakan',
        ]);
        Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan Berlangsung Uji',
            'status' => 'Berlangsung',
        ]);

        $html = $this->get('/agenda')->assertOk()->getContent();

        // Kedua kegiatan tetap ditampilkan.
        $this->assertStringContainsString('Kegiatan Direncanakan Uji', $html);
        $this->assertStringContainsString('Kegiatan Berlangsung Uji', $html);

        // Status masing-masing tetap dirender dengan benar.
        $this->assertStringContainsString('Berlangsung', $html);
        $this->assertStringContainsString('Akan Datang', $html);

        // Tidak ada kegiatan berstatus "Dibatalkan" pada skenario ini.
        $this->assertSame(0, substr_count($html, 'Dibatalkan'));
    }
}
