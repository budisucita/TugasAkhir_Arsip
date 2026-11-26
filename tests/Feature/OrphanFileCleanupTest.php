<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Surat;
use App\Models\TemplateSurat;
use App\Support\SecureFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji pembersihan berkas fisik (orphan) saat record dihapus.
 *
 * Skenario:
 * - Menghapus Kegiatan membersihkan proposal, RAB, dan LPJ.
 * - Menghapus Anggota membersihkan foto.
 * - Menghapus Surat membersihkan berkas surat.
 * - Menghapus Template Surat membersihkan berkas template.
 * - Penghapusan tetap aman ketika berkas sudah tidak ada (tanpa error).
 */
class OrphanFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(SecureFile::diskName());
        config()->set('filesystems.default', 'public');
        Storage::fake('public');
    }

    /**
     * Simpan berkas dummy pada disk privat dan kembalikan path-nya.
     */
    private function putPrivate(string $path): string
    {
        Storage::disk(SecureFile::diskName())->put($path, 'berkas-uji');

        return $path;
    }

    public function test_menghapus_kegiatan_menghapus_berkas_proposal_rab_lpj(): void
    {
        $proposal = $this->putPrivate('kegiatan/proposal/p.pdf');
        $rab = $this->putPrivate('kegiatan/rab/r.pdf');
        $lpj = $this->putPrivate('kegiatan/lpj/l.pdf');

        $kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Uji',
            'status' => 'Selesai',
            'tanggal_mulai' => now()->toDateString(),
            'proposal_file' => $proposal,
            'rab_file' => $rab,
            'lpj_file' => $lpj,
        ]);

        $kegiatan->delete();

        foreach ([$proposal, $rab, $lpj] as $path) {
            $this->assertFalse(
                Storage::disk(SecureFile::diskName())->exists($path),
                "Berkas {$path} seharusnya ikut terhapus."
            );
        }
    }

    public function test_menghapus_anggota_menghapus_foto(): void
    {
        $foto = $this->putPrivate('anggota/foto/a.jpg');

        $anggota = Anggota::create([
            'nama' => 'Anggota Uji',
            'foto' => $foto,
        ]);

        $anggota->delete();

        $this->assertFalse(Storage::disk(SecureFile::diskName())->exists($foto));
    }

    public function test_menghapus_surat_menghapus_file_surat(): void
    {
        $file = $this->putPrivate('surat/file/s.pdf');

        $surat = Surat::create([
            'no_surat' => 'SR-'.uniqid(),
            'pengirim' => 'Instansi Uji',
            'tanggal_terima' => now()->toDateString(),
            'status' => 'masuk',
            'perihal' => 'Uji',
            'file_surat' => $file,
        ]);

        $surat->delete();

        $this->assertFalse(Storage::disk(SecureFile::diskName())->exists($file));
    }

    public function test_menghapus_template_surat_menghapus_file_template(): void
    {
        // Template surat bersifat internal: tersimpan di disk privat.
        $path = 'template-surat/template.docx';
        Storage::disk(SecureFile::diskName())->put($path, 'template-bytes');

        $template = TemplateSurat::create([
            'nama_dokumen' => 'Template Uji',
            'file_path' => $path,
        ]);

        $template->delete();

        $this->assertFalse(Storage::disk(SecureFile::diskName())->exists($path));
    }

    public function test_penghapusan_tetap_aman_saat_berkas_tidak_ditemukan(): void
    {
        // Record menunjuk berkas yang tidak ada di disk: penghapusan harus tetap sukses.
        $kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Uji Tanpa Berkas',
            'status' => 'Selesai',
            'tanggal_mulai' => now()->toDateString(),
            'proposal_file' => 'kegiatan/proposal/tidak-ada.pdf',
        ]);

        $id = $kegiatan->getKey();
        $kegiatan->delete();

        $this->assertDatabaseMissing('kegiatan', ['id' => $id]);
    }
}
