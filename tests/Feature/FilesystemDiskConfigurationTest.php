<?php

namespace Tests\Feature;

use App\Models\Dokumentasi;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Support\SecureFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji standardisasi konfigurasi sistem file:
 *
 * - Disk publik (FILESYSTEM_DISK) khusus galeri dokumentasi yang dapat diakses publik.
 * - Disk privat (FILESYSTEM_PRIVATE_DISK) khusus berkas internal yang terlindungi.
 * - URL berkas dokumentasi selalu mengikuti disk default (dinamis), tidak di-hardcode.
 * - Perpindahan ke S3 tidak menyebabkan URL menunjuk ke lokasi /storage yang salah (404).
 */
class FilesystemDiskConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_env_example_menetapkan_disk_publik_dan_privat_secara_terpisah(): void
    {
        $envExample = File::get(base_path('.env.example'));

        $this->assertStringContainsString('FILESYSTEM_DISK=public', $envExample);
        $this->assertStringContainsString('FILESYSTEM_PRIVATE_DISK=private', $envExample);
    }

    public function test_disk_publik_dan_privat_merupakan_konfigurasi_terpisah(): void
    {
        $this->assertNotSame(
            config('filesystems.default'),
            config('filesystems.private_disk'),
            'Disk publik dan disk privat tidak boleh memakai nama disk yang sama.'
        );

        $this->assertSame('private', config('filesystems.disks.'.config('filesystems.private_disk').'.visibility'));
    }

    public function test_dokumentasi_galeri_memakai_disk_publik_default(): void
    {
        config()->set('filesystems.default', 'public');
        Storage::fake('public', ['url' => 'http://localhost/storage']);

        $path = 'dokumentasi/file/foto-galeri.jpg';
        Storage::disk('public')->put($path, 'image-bytes');

        $dokumentasi = $this->makeDokumentasi($path);

        // URL disk publik kini relatif (tanpa prefiks APP_URL) agar aman saat
        // diakses melalui proxy HTTPS (mis. Ngrok) tanpa memicu mixed content.
        $this->assertSame('/storage/'.$path, $dokumentasi->file_url);
        $this->assertStringContainsString('/storage/', $dokumentasi->file_url);
        $this->assertStringNotContainsString('/secure-file/', $dokumentasi->file_url);
    }

    public function test_url_dokumentasi_mengikuti_disk_default_saat_berpindah_ke_s3(): void
    {
        Storage::fake('public', ['url' => 'http://localhost/storage']);
        Storage::fake('s3', ['url' => 'https://bucket.example.com']);

        $path = 'dokumentasi/file/foto-s3.jpg';

        // Sebelum pindah: memakai disk publik lokal.
        config()->set('filesystems.default', 'public');
        $this->assertStringContainsString('/storage/', $this->makeDokumentasi($path)->file_url);

        // Setelah pindah ke S3: URL mengikuti disk default (bukan hardcode 'public').
        config()->set('filesystems.default', 's3');
        $url = $this->makeDokumentasi($path)->file_url;

        $this->assertSame('https://bucket.example.com/'.$path, $url);
        $this->assertStringContainsString('bucket.example.com', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_berkas_internal_tetap_terlindungi_di_disk_privat_walau_disk_publik_aktif(): void
    {
        config()->set('filesystems.default', 'public');
        Storage::fake('public');
        Storage::fake(SecureFile::diskName());

        $path = 'keuangan/bukti/rahasia.pdf';
        Storage::disk(SecureFile::diskName())->put($path, '%PDF-1.4 berkas internal');

        // Berkas ada di disk privat, tidak ada di disk publik.
        $this->assertTrue(Storage::disk(SecureFile::diskName())->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));

        // URL berkas internal memakai rute terproteksi, bukan /storage publik.
        $keuangan = Keuangan::create([
            'jenis_transaksi' => 'pengeluaran',
            'jumlah' => 1000,
            'keterangan' => 'Transaksi uji',
            'bukti_file' => $path,
            'tanggal' => now()->toDateString(),
        ]);

        $this->assertStringContainsString('/secure-file/', $keuangan->bukti_url);
        $this->assertStringNotContainsString('/storage/', $keuangan->bukti_url);
    }

    public function test_halaman_galeri_menampilkan_url_disk_publik_tanpa_error(): void
    {
        $this->withoutVite();

        config()->set('filesystems.default', 'public');
        Storage::fake('public', ['url' => 'http://localhost/storage']);

        $path = 'dokumentasi/file/galeri-publik.jpg';
        Storage::disk('public')->put($path, 'image-bytes');

        $this->makeDokumentasi($path);

        $this->get('/galeri')
            ->assertOk()
            ->assertSee('/storage/'.$path, false);
    }

    public function test_tidak_ada_hardcode_disk_publik_pada_resource_dan_komponen(): void
    {
        $files = [
            app_path('Filament/Resources/KegiatanResource.php'),
            app_path('Filament/Resources/KeuanganResource.php'),
            app_path('Filament/Resources/TemplateSuratResource.php'),
            resource_path('views/filament/infolists/components/pdf-viewer.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = File::get($file);

            $this->assertStringNotContainsString("->disk('public')", $contents, "Hardcode disk publik ditemukan di {$file}");
            $this->assertStringNotContainsString("Storage::disk('public')", $contents, "Hardcode disk publik ditemukan di {$file}");
        }
    }

    /**
     * Buat record dokumentasi galeri yang menunjuk berkas di disk publik.
     */
    private function makeDokumentasi(string $path): Dokumentasi
    {
        return Dokumentasi::create([
            'kegiatan_id' => Kegiatan::factory()->create()->getKey(),
            'tipe' => 'foto',
            'file_path' => $path,
            'deskripsi' => 'Dokumentasi uji',
        ]);
    }
}
