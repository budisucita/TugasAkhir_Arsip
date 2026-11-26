<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\User;
use App\Support\SecureFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji proxy unduhan berkas privat lewat rute /admin/dokumen/{tipe}/{id}.
 *
 * Fokus:
 * - Tamu diarahkan ke login, pengguna tanpa izin ditolak Policy (403).
 * - Pengguna berizin (admin/pengurus) dapat mengunduh berkas privat.
 * - Tipe tidak dikenal / record tidak ada -> 404.
 * - Pemilihan kolom berkas (mis. RAB) lewat query ?field= berfungsi.
 * - Implementasi tidak mengganggu rute pratinjau lama (secure-file.show).
 */
class AdminDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk privat diisolasi agar pengujian tidak menyentuh berkas nyata.
        Storage::fake(SecureFile::diskName());
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $keuangan = $this->makeKeuangan();
        $url = route('admin.dokumen.download', ['tipe' => 'keuangan', 'id' => $keuangan->id]);

        $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_admin_dapat_mengunduh_bukti_transaksi(): void
    {
        $contents = '%PDF-1.4 bukti-transaksi';
        $keuangan = $this->makeKeuangan($contents);
        $url = route('admin.dokumen.download', ['tipe' => 'keuangan', 'id' => $keuangan->id]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url);

        $response->assertOk();
        $this->assertSame($contents, $response->streamedContent());
    }

    public function test_pengurus_dapat_mengunduh_berkas(): void
    {
        $keuangan = $this->makeKeuangan('%PDF-1.4 pengurus');
        $url = route('admin.dokumen.download', ['tipe' => 'keuangan', 'id' => $keuangan->id]);

        $this->actingAs(User::factory()->create(['role' => 'pengurus']))
            ->get($url)
            ->assertOk();
    }

    public function test_pengguna_tanpa_izin_ditolak_policy(): void
    {
        $keuangan = $this->makeKeuangan();
        $url = route('admin.dokumen.download', ['tipe' => 'keuangan', 'id' => $keuangan->id]);

        $this->actingAs(User::factory()->make(['role' => 'tamu']))
            ->get($url)
            ->assertForbidden();
    }

    public function test_tipe_tidak_dikenal_ditolak_404(): void
    {
        $url = route('admin.dokumen.download', ['tipe' => 'tidak-ada', 'id' => 1]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get($url)
            ->assertNotFound();
    }

    public function test_record_tidak_ditemukan_ditolak_404(): void
    {
        $url = route('admin.dokumen.download', ['tipe' => 'keuangan', 'id' => 999999]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get($url)
            ->assertNotFound();
    }

    public function test_dapat_memilih_kolom_berkas_kegiatan_lewat_query(): void
    {
        Storage::disk(SecureFile::diskName())->put('kegiatan/proposal/prop.pdf', '%PDF-1.4 proposal');
        Storage::disk(SecureFile::diskName())->put('kegiatan/rab/rab.pdf', '%PDF-1.4 rab');

        $kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Uji',
            'tanggal_mulai' => '2026-01-01',
            'proposal_file' => 'kegiatan/proposal/prop.pdf',
            'rab_file' => 'kegiatan/rab/rab.pdf',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $url = route('admin.dokumen.download', [
            'tipe' => 'kegiatan',
            'id' => $kegiatan->id,
            'field' => 'rab_file',
        ]);

        $this->assertSame('%PDF-1.4 rab', $this->get($url)->streamedContent());
    }

    public function test_kolom_default_adalah_berkas_pertama(): void
    {
        Storage::disk(SecureFile::diskName())->put('kegiatan/proposal/prop.pdf', '%PDF-1.4 proposal');

        $kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Uji',
            'tanggal_mulai' => '2026-01-01',
            'proposal_file' => 'kegiatan/proposal/prop.pdf',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $url = route('admin.dokumen.download', ['tipe' => 'kegiatan', 'id' => $kegiatan->id]);

        $this->assertSame('%PDF-1.4 proposal', $this->get($url)->streamedContent());
    }

    public function test_rute_pratinjau_lama_tetap_berfungsi(): void
    {
        $keuangan = $this->makeKeuangan('%PDF-1.4 pratinjau');
        $url = SecureFile::urlFor($keuangan, 'bukti_file');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url);

        $response->assertOk();
        $this->assertSame('%PDF-1.4 pratinjau', $response->streamedContent());
    }

    private function makeKeuangan(string $contents = '%PDF-1.4 bukti'): Keuangan
    {
        $path = 'keuangan/bukti/bukti-' . uniqid() . '.pdf';
        Storage::disk(SecureFile::diskName())->put($path, $contents);

        return Keuangan::create([
            'jenis_transaksi' => 'pengeluaran',
            'jumlah' => 1000,
            'keterangan' => 'Transaksi uji',
            'bukti_file' => $path,
            'tanggal' => now()->toDateString(),
        ]);
    }
}
