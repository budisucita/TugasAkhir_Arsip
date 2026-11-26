<?php

namespace Tests\Feature;

use App\Filament\Resources\TemplateSuratResource\Pages\CreateTemplateSurat;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Widgets\StatsOverview;
use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Surat;
use App\Models\User;
use App\Support\SecureFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji perbaikan empat temuan frontend/console:
 *
 * 1. Konfigurasi loop Swiper menyesuaikan jumlah slide (tanpa peringatan console).
 * 2. Filter peran admin pada UserResource tidak lagi kosong (bentrok query dasar).
 * 3. Unggahan Template Surat tidak menimpa berkas lama (nama berkas unik).
 * 4. Cache statistik Dashboard dibersihkan saat Anggota/Kegiatan/Surat berubah.
 */
class FrontendIssuesRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ---------------------------------------------------------------------
    // 1. Swiper loop dinamis
    // ---------------------------------------------------------------------

    public function test_konfigurasi_swiper_memakai_loop_dinamis_bukan_hardcoded(): void
    {
        $source = File::get(resource_path('views/welcome.blade.php'));

        // Loop tidak lagi dipaksa selalu aktif.
        $this->assertStringNotContainsString('loop: true,', $source);

        // Loop & loopedSlides menyesuaikan jumlah slide yang tersedia.
        $this->assertStringContainsString("querySelectorAll('.swiper-slide')", $source);
        $this->assertStringContainsString('loop: slideCount > 1', $source);
        $this->assertStringContainsString('loopedSlides: Math.min(6, slideCount)', $source);
    }

    public function test_slide_fallback_tetap_tampil_saat_tidak_ada_dokumentasi(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Tanpa dokumentasi, hanya satu slide fallback yang dirender.
        $this->assertSame(1, substr_count($html, 'class="swiper-slide'));
        $this->assertStringContainsString('placehold.co/1600x900', $html);
    }

    // ---------------------------------------------------------------------
    // 2. Filter peran admin pada UserResource
    // ---------------------------------------------------------------------

    public function test_query_dasar_admin_tidak_mengunci_satu_peran(): void
    {
        $this->actingAs($this->admin());
        User::factory()->create(['role' => 'pengurus']);

        $peran = UserResource::getEloquentQuery()->pluck('role')->unique()->values()->all();

        // Query dasar harus memuat kedua peran agar filter dapat bekerja.
        $this->assertContains('admin', $peran);
        $this->assertContains('pengurus', $peran);
    }

    public function test_filter_peran_admin_menampilkan_pengguna_admin(): void
    {
        $admin = $this->admin();
        $adminLain = User::factory()->create(['role' => 'admin']);
        $pengurus = User::factory()->create(['role' => 'pengurus']);

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->filterTable('role', 'admin')
            ->assertCanSeeTableRecords([$adminLain])
            ->assertCanNotSeeTableRecords([$pengurus]);
    }

    public function test_filter_peran_pengurus_menampilkan_pengguna_pengurus(): void
    {
        $admin = $this->admin();
        $adminLain = User::factory()->create(['role' => 'admin']);
        $pengurus = User::factory()->create(['role' => 'pengurus']);

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->filterTable('role', 'pengurus')
            ->assertCanSeeTableRecords([$pengurus])
            ->assertCanNotSeeTableRecords([$adminLain]);
    }

    // ---------------------------------------------------------------------
    // 3. Penimpaan berkas Template Surat
    // ---------------------------------------------------------------------

    public function test_resource_template_tidak_lagi_memakai_preserve_filenames(): void
    {
        $source = File::get(app_path('Filament/Resources/TemplateSuratResource.php'));

        $this->assertStringNotContainsString('->preserveFilenames()', $source);
    }

    public function test_unggahan_template_dengan_nama_sama_tidak_saling_menimpa(): void
    {
        // Template surat bersifat internal: berkas disimpan di disk privat.
        Storage::fake(SecureFile::diskName());

        $admin = $this->admin();
        $this->actingAs($admin);

        // Dua unggahan dengan nama berkas asli yang identik.
        foreach (['Template A', 'Template B'] as $nama) {
            Livewire::test(CreateTemplateSurat::class)
                ->fillForm([
                    'nama_dokumen' => $nama,
                    'file_path' => [UploadedFile::fake()->create('template.pdf', 128, 'application/pdf')],
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        // Dua berkas tersimpan terpisah (nama unik) — tidak saling menimpa.
        $files = Storage::disk(SecureFile::diskName())->files('template-surat');
        $this->assertCount(2, $files);
    }

    // ---------------------------------------------------------------------
    // 4. Pembersihan cache statistik Dashboard
    // ---------------------------------------------------------------------

    /**
     * Populasi cache statistik dengan membaca widget, lalu mengembalikan
     * closure pembaca nilai untuk verifikasi.
     */
    private function populateStatsCache(): void
    {
        Cache::flush();

        $widget = new StatsOverview();
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $method->invoke($widget);

        $this->assertNotNull(
            Cache::get(StatsOverview::CACHE_KEY),
            'Cache statistik seharusnya terisi setelah widget dibaca.'
        );
    }

    public function test_cache_statistik_dibersihkan_saat_anggota_berubah(): void
    {
        $this->populateStatsCache();

        Anggota::create(['nama' => 'Anggota Uji', 'status' => 'aktif']);

        $this->assertNull(Cache::get(StatsOverview::CACHE_KEY));
    }

    public function test_cache_statistik_dibersihkan_saat_kegiatan_berubah(): void
    {
        $this->populateStatsCache();

        Kegiatan::factory()->create(['status' => 'Berlangsung']);

        $this->assertNull(Cache::get(StatsOverview::CACHE_KEY));
    }

    public function test_cache_statistik_dibersihkan_saat_surat_berubah(): void
    {
        $this->populateStatsCache();

        Surat::create([
            'no_surat' => '001/UJI/2026',
            'pengirim' => 'Dinas Uji',
            'tanggal_terima' => now(),
            'status' => 'masuk',
            'perihal' => 'Surat uji',
        ]);

        $this->assertNull(Cache::get(StatsOverview::CACHE_KEY));
    }

    public function test_statistik_dashboard_menampilkan_data_terbaru_setelah_perubahan(): void
    {
        Cache::flush();

        $widget = new StatsOverview();
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        // Populasi cache dengan kondisi awal (0 anggota).
        $method->invoke($widget);

        // Tambah data → cache invalidated oleh observer.
        Anggota::create(['nama' => 'Anggota Uji', 'status' => 'aktif']);
        Surat::create([
            'no_surat' => '002/UJI/2026',
            'pengirim' => 'Dinas Uji',
            'tanggal_terima' => now(),
            'status' => 'keluar',
            'perihal' => 'Surat uji kedua',
        ]);

        $nilai = [];
        foreach ($method->invoke($widget) as $stat) {
            $nilai[$stat->getLabel()] = (int) $stat->getValue();
        }

        $this->assertSame(1, $nilai['Total Anggota']);
        $this->assertSame(1, $nilai['Total Surat']);
    }
}
