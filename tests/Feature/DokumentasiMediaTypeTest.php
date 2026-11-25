<?php

namespace Tests\Feature;

use App\Filament\Resources\DokumentasiResource\Pages\CreateDokumentasi;
use App\Filament\Resources\KegiatanResource\Pages\EditKegiatan;
use App\Filament\Resources\KegiatanResource\RelationManagers\DokumentasiRelationManager;
use App\Models\Dokumentasi;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji konsistensi kebijakan tipe media dokumentasi agenda.
 *
 * Kebijakan: mendukung gambar (JPG/PNG) dan video (MP4) secara seragam pada
 * kedua jalur unggah (resource utama & relation manager) serta halaman detail.
 */
class DokumentasiMediaTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kegiatan(): Kegiatan
    {
        return Kegiatan::factory()->create();
    }

    private function dokumentasi(Kegiatan $kegiatan, string $tipe, string $namaFile): Dokumentasi
    {
        return Dokumentasi::create([
            'kegiatan_id' => $kegiatan->id,
            'tipe' => $tipe,
            'file_path' => 'dokumentasi/file/' . $namaFile,
            'deskripsi' => 'Dokumentasi uji',
        ]);
    }

    /**
     * Memastikan tepat satu berkas tersimpan pada disk dengan ekstensi sesuai.
     */
    private function assertBerkasTersimpan(string $namafile): void
    {
        $files = Storage::disk(config('filesystems.default'))->files('dokumentasi/file');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.' . pathinfo($namafile, PATHINFO_EXTENSION), $files[0]);
    }

    // ---------------------------------------------------------------------
    // 1. Jalur resource utama (DokumentasiResource)
    // ---------------------------------------------------------------------

    /**
     * @dataProvider tipeDiizinkanProvider
     */
    public function test_resource_utama_menerima_semua_tipe_yang_diizinkan(string $namafile, string $mime, string $tipe): void
    {
        $this->actingAs($this->admin());
        $kegiatan = $this->kegiatan();

        Livewire::test(CreateDokumentasi::class)
            ->fillForm([
                'kegiatan_id' => $kegiatan->id,
                'tipe' => $tipe,
                'file_path' => [UploadedFile::fake()->create($namafile, 512, $mime)],
                'deskripsi' => 'Dokumentasi uji',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dokumentasi', [
            'kegiatan_id' => $kegiatan->id,
            'tipe' => $tipe,
        ]);

        $this->assertBerkasTersimpan($namafile);
    }

    public function test_resource_utama_menolak_tipe_yang_tidak_diizinkan(): void
    {
        $this->actingAs($this->admin());
        $kegiatan = $this->kegiatan();

        Livewire::test(CreateDokumentasi::class)
            ->fillForm([
                'kegiatan_id' => $kegiatan->id,
                'tipe' => 'foto',
                'file_path' => [UploadedFile::fake()->create('dokumen.pdf', 128, 'application/pdf')],
                'deskripsi' => 'Dokumen uji',
            ])
            ->call('create')
            ->assertHasFormErrors(['file_path']);

        $this->assertDatabaseCount('dokumentasi', 0);
    }

    // ---------------------------------------------------------------------
    // 2. Jalur relation manager (DokumentasiRelationManager)
    // ---------------------------------------------------------------------

    /**
     * @dataProvider tipeDiizinkanProvider
     */
    public function test_relation_manager_menerima_semua_tipe_yang_diizinkan(string $namafile, string $mime, string $tipe): void
    {
        $this->actingAs($this->admin());
        $kegiatan = $this->kegiatan();

        Livewire::test(DokumentasiRelationManager::class, [
            'ownerRecord' => $kegiatan,
            'pageClass' => EditKegiatan::class,
        ])
            ->callTableAction('create', data: [
                'tipe' => $tipe,
                'file_path' => [UploadedFile::fake()->create($namafile, 512, $mime)],
                'deskripsi' => 'Dokumentasi uji',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('dokumentasi', [
            'kegiatan_id' => $kegiatan->id,
            'tipe' => $tipe,
        ]);

        $this->assertBerkasTersimpan($namafile);
    }

    public function test_relation_manager_menolak_tipe_yang_tidak_diizinkan(): void
    {
        $this->actingAs($this->admin());
        $kegiatan = $this->kegiatan();

        Livewire::test(DokumentasiRelationManager::class, [
            'ownerRecord' => $kegiatan,
            'pageClass' => EditKegiatan::class,
        ])
            ->callTableAction('create', data: [
                'tipe' => 'foto',
                'file_path' => [UploadedFile::fake()->create('dokumen.pdf', 128, 'application/pdf')],
                'deskripsi' => 'Dokumen uji',
            ])
            ->assertHasTableActionErrors(['file_path']);

        $this->assertDatabaseCount('dokumentasi', 0);
    }

    // ---------------------------------------------------------------------
    // 3. Rendering halaman detail agenda publik
    // ---------------------------------------------------------------------

    public function test_halaman_detail_merender_video_dengan_tag_video(): void
    {
        $kegiatan = $this->kegiatan();
        $this->dokumentasi($kegiatan, 'video', 'video-uji.mp4');

        $html = $this->get('/agenda/' . $kegiatan->id)->assertOk()->getContent();

        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('controls', $html);
        $this->assertStringContainsString('video-uji.mp4', $html);
    }

    public function test_halaman_detail_merender_foto_dengan_tag_img(): void
    {
        $kegiatan = $this->kegiatan();
        $this->dokumentasi($kegiatan, 'foto', 'foto-uji.jpg');

        $html = $this->get('/agenda/' . $kegiatan->id)->assertOk()->getContent();

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('foto-uji.jpg', $html);
        $this->assertStringNotContainsString('<video', $html);
    }

    public function test_galeri_merender_video_dan_foto_sesuai_tipe(): void
    {
        $kegiatan = $this->kegiatan();
        $this->dokumentasi($kegiatan, 'foto', 'foto-galeri.jpg');
        $this->dokumentasi($kegiatan, 'video', 'video-galeri.mp4');

        $html = $this->get('/agenda/' . $kegiatan->id)->assertOk()->getContent();

        // Kedua tipe media tampil dengan tag HTML yang sesuai.
        $this->assertStringContainsString('foto-galeri.jpg', $html);
        $this->assertStringContainsString('video-galeri.mp4', $html);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('<img', $html);
    }

    /**
     * Tipe file yang diizinkan secara seragam pada kedua jalur unggah.
     */
    public static function tipeDiizinkanProvider(): array
    {
        return [
            'jpeg' => ['foto-uji.jpg', 'image/jpeg', 'foto'],
            'png' => ['foto-uji.png', 'image/png', 'foto'],
            'mp4' => ['video-uji.mp4', 'video/mp4', 'video'],
        ];
    }
}
