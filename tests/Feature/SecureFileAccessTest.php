<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Surat;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Support\SecureFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji perlindungan berkas sensitif (bukti transaksi, surat resmi,
 * proposal, RAB, LPJ, dan foto anggota) yang dipindahkan ke disk privat.
 *
 * Skenario:
 * - Tamu (belum login) ditolak.
 * - Pengguna login tanpa izin ditolak Policy (403).
 * - Pengguna berizin (admin/pengurus) dapat mengakses berkas.
 * - Tidak ada URL /storage/... yang bocor pada respons resource.
 */
class SecureFileAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk privat diisolasi agar pengujian tidak menyentuh berkas nyata.
        Storage::fake(SecureFile::diskName());
    }

    public function test_tamu_ditolak_mengakses_berkas_terproteksi(): void
    {
        $keuangan = $this->makeKeuangan();
        $url = SecureFile::urlFor($keuangan, 'bukti_file');

        $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_pengguna_login_tanpa_izin_ditolak_policy(): void
    {
        $keuangan = $this->makeKeuangan();
        $url = SecureFile::urlFor($keuangan, 'bukti_file');

        // Peran di luar admin/pengurus: dilarang oleh Policy meskipun sudah login.
        $this->actingAs(new User(['role' => 'tamu']));

        $this->get($url)->assertForbidden();
    }

    public function test_admin_berizin_dapat_mengakses_berkas(): void
    {
        $contents = '%PDF-1.4 berkas-rahasia';

        $keuangan = $this->makeKeuangan($contents);
        $url = SecureFile::urlFor($keuangan, 'bukti_file');

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->get($url);

        $response->assertOk();
        $this->assertSame($contents, $response->streamedContent());
    }

    public function test_pengurus_berizin_dapat_mengakses_berkas(): void
    {
        $keuangan = $this->makeKeuangan('%PDF-1.4 berkas-pengurus');
        $url = SecureFile::urlFor($keuangan, 'bukti_file');

        $this->actingAs(User::factory()->create(['role' => 'pengurus']));

        $this->get($url)->assertOk();
    }

    public function test_semua_tipe_berkas_sensitif_dapat_disajikan_untuk_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach ($this->sensitiveFiles() as $type => $case) {
            $url = route('secure-file.show', [
                'type' => $type,
                'id' => $case['record']->getKey(),
                'field' => $case['field'],
            ]);

            $response = $this->get($url);

            $response->assertOk();
            $this->assertSame($case['contents'], $response->streamedContent(), "Isi berkas tipe {$type} tidak sesuai.");
        }
    }

    public function test_kolom_bukan_berkas_sensitif_ditolak(): void
    {
        $keuangan = $this->makeKeuangan();

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $url = route('secure-file.show', [
            'type' => 'keuangan',
            'id' => $keuangan->getKey(),
            'field' => 'keterangan',
        ]);

        $this->get($url)->assertNotFound();
    }

    public function test_tipe_tidak_dikenal_ditolak(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $url = route('secure-file.show', [
            'type' => 'tidak-ada',
            'id' => 1,
            'field' => 'apa-saja',
        ]);

        $this->get($url)->assertNotFound();
    }

    public function test_berkas_tidak_ada_ditolak(): void
    {
        // Record menunjuk berkas yang tidak ada di disk privat.
        $keuangan = $this->makeKeuangan(storeFile: false);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $url = SecureFile::urlFor($keuangan, 'bukti_file');

        $this->get($url)->assertNotFound();
    }

    public function test_accessor_mengembalikan_rute_terproteksi_bukan_storage_publik(): void
    {
        $keuangan = $this->makeKeuangan();

        $url = $keuangan->bukti_url;

        $this->assertNotNull($url);
        $this->assertStringContainsString('/secure-file/', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_berkas_privat_tidak_terekspos_di_disk_publik(): void
    {
        $path = 'keuangan/bukti/rahasia.pdf';

        Storage::disk(SecureFile::diskName())->put($path, '%PDF-1.4 rahasia');

        // Berkas tersedia di disk privat...
        $this->assertTrue(Storage::disk(SecureFile::diskName())->exists($path));

        // ...tetapi tidak ada di disk publik sehingga tidak bisa diakses via /storage/...
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_tabel_dan_form_anggota_menampilkan_foto_privat_tanpa_error(): void
    {
        Storage::disk(SecureFile::diskName())->put('anggota/foto/a.jpg', 'anggota-image');

        $anggota = Anggota::create([
            'nama' => 'Anggota Uji',
            'foto' => 'anggota/foto/a.jpg',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        // Tabel (ImageColumn) dan form (FileUpload) memakai rute terproteksi.
        $this->get('/admin/anggotas')->assertSuccessful();
        $this->get('/admin/anggotas/'.$anggota->getKey().'/edit')->assertSuccessful();
    }

    public function test_form_edit_keuangan_menampilkan_bukti_privat_tanpa_error(): void
    {
        $keuangan = $this->makeKeuangan('%PDF-1.4 bukti');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/keuangans/'.$keuangan->getKey().'/edit')
            ->assertSuccessful();
    }

    public function test_tamu_ditolak_mengakses_berkas_template_surat(): void
    {
        $template = $this->makeTemplateSurat();
        $url = SecureFile::urlFor($template, 'file_path');

        $this->assertStringContainsString('/secure-file/', (string) $url);

        $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_pengguna_tanpa_izin_ditolak_mengakses_berkas_template_surat(): void
    {
        $template = $this->makeTemplateSurat();
        $url = SecureFile::urlFor($template, 'file_path');

        // Peran di luar admin/pengurus ditolak Policy meskipun sudah login.
        $this->actingAs(new User(['role' => 'tamu']));

        $this->get($url)->assertForbidden();
    }

    public function test_admin_dapat_mengakses_berkas_template_surat(): void
    {
        $contents = '%PDF-1.4 template-rahasia';
        $template = $this->makeTemplateSurat($contents);
        $url = SecureFile::urlFor($template, 'file_path');

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->get($url);

        $response->assertOk();
        $this->assertSame($contents, $response->streamedContent());
    }

    public function test_berkas_template_surat_tidak_terekspos_di_disk_publik(): void
    {
        $path = 'template-surat/internal.docx';

        Storage::disk(SecureFile::diskName())->put($path, 'template-bytes');

        // Tersedia di disk privat...
        $this->assertTrue(Storage::disk(SecureFile::diskName())->exists($path));

        // ...tetapi tidak ada di disk publik sehingga tidak bisa diakses via /storage/...
        $this->assertFalse(Storage::disk(config('filesystems.default'))->exists($path));
    }

    public function test_halaman_template_surat_menampilkan_berkas_privat_tanpa_error(): void
    {
        $this->makeTemplateSurat();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/template-surats')
            ->assertSuccessful();
    }

    /**
     * Buat record template surat beserta berkas di disk privat.
     */
    private function makeTemplateSurat(string $contents = '%PDF-1.4 template'): TemplateSurat
    {
        $path = 'template-surat/template-'.uniqid().'.pdf';

        Storage::disk(SecureFile::diskName())->put($path, $contents);

        return TemplateSurat::create([
            'nama_dokumen' => 'Template Uji',
            'file_path' => $path,
        ]);
    }

    /**
     * Buat record keuangan beserta berkas bukti di disk privat.
     */
    private function makeKeuangan(string $contents = '%PDF-1.4 bukti', bool $storeFile = true): Keuangan
    {
        $path = 'keuangan/bukti/bukti-'.uniqid().'.pdf';

        if ($storeFile) {
            Storage::disk(SecureFile::diskName())->put($path, $contents);
        }

        return Keuangan::create([
            'jenis_transaksi' => 'pengeluaran',
            'jumlah' => 1000,
            'keterangan' => 'Transaksi uji',
            'bukti_file' => $path,
            'tanggal' => now()->toDateString(),
        ]);
    }

    /**
     * Buat satu record per tipe berkas sensitif beserta berkas di disk privat.
     *
     * @return array<string, array{record: \Illuminate\Database\Eloquent\Model, field: string, contents: string}>
     */
    private function sensitiveFiles(): array
    {
        $keuanganPath = 'keuangan/bukti/k.pdf';
        $suratPath = 'surat/file/s.pdf';
        $kegiatanPath = 'kegiatan/proposal/p.pdf';
        $anggotaPath = 'anggota/foto/a.jpg';
        $templatePath = 'template-surat/t.docx';

        Storage::disk(SecureFile::diskName())->put($keuanganPath, '%PDF-1.4 keuangan');
        Storage::disk(SecureFile::diskName())->put($suratPath, '%PDF-1.4 surat');
        Storage::disk(SecureFile::diskName())->put($kegiatanPath, '%PDF-1.4 kegiatan');
        Storage::disk(SecureFile::diskName())->put($anggotaPath, 'anggota-image');
        Storage::disk(SecureFile::diskName())->put($templatePath, '%PDF-1.4 template');

        $keuangan = Keuangan::create([
            'jenis_transaksi' => 'pemasukan',
            'jumlah' => 500,
            'keterangan' => 'Uji',
            'bukti_file' => $keuanganPath,
            'tanggal' => now()->toDateString(),
        ]);

        $surat = Surat::create([
            'no_surat' => 'SR-'.uniqid(),
            'pengirim' => 'Instansi Uji',
            'tanggal_terima' => now()->toDateString(),
            'status' => 'masuk',
            'perihal' => 'Uji',
            'file_surat' => $suratPath,
        ]);

        $kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Uji',
            'tanggal_mulai' => now()->toDateString(),
            'proposal_file' => $kegiatanPath,
        ]);

        $anggota = Anggota::create([
            'nama' => 'Anggota Uji',
            'foto' => $anggotaPath,
        ]);

        $template = TemplateSurat::create([
            'nama_dokumen' => 'Template Uji',
            'file_path' => $templatePath,
        ]);

        return [
            'keuangan' => ['record' => $keuangan, 'field' => 'bukti_file', 'contents' => '%PDF-1.4 keuangan'],
            'surat' => ['record' => $surat, 'field' => 'file_surat', 'contents' => '%PDF-1.4 surat'],
            'kegiatan' => ['record' => $kegiatan, 'field' => 'proposal_file', 'contents' => '%PDF-1.4 kegiatan'],
            'anggota' => ['record' => $anggota, 'field' => 'foto', 'contents' => 'anggota-image'],
            'template_surat' => ['record' => $template, 'field' => 'file_path', 'contents' => '%PDF-1.4 template'],
        ];
    }
}
