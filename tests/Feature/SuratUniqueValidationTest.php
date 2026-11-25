<?php

namespace Tests\Feature;

use App\Filament\Resources\SuratResource\Pages\CreateSurat;
use App\Filament\Resources\SuratResource\Pages\EditSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji validasi keunikan kolom "no_surat" pada SuratResource.
 *
 * Fokus:
 * - Mengedit record yang sudah ada tanpa mengubah no_surat tidak memicu error
 *   (SQL 1062) maupun error validasi palsu (ignoreRecord: true).
 * - Menyimpan no_surat yang benar-benar duplikat ditolak lewat validasi form,
 *   bukan error SQL mentah.
 */
class SuratUniqueValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk privat dipalsukan agar unggahan berkas surat dapat divalidasi.
        Storage::fake(config('filesystems.private_disk', 'private'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Membuat record surat lengkap dengan berkas privat tiruan.
     */
    private function surat(string $noSurat): Surat
    {
        $path = 'surat/file/' . Str::uuid() . '.pdf';
        Storage::disk(config('filesystems.private_disk', 'private'))
            ->put($path, '%PDF-1.4 berkas uji');

        return Surat::create([
            'no_surat' => $noSurat,
            'pengirim' => 'Dinas Uji',
            'tanggal_terima' => now()->toDateString(),
            'status' => 'masuk',
            'perihal' => 'Perihal uji',
            'file_surat' => $path,
        ]);
    }

    public function test_edit_surat_tanpa_mengubah_no_surat_tidak_memicu_error(): void
    {
        $this->actingAs($this->admin());
        $surat = $this->surat('001/SURAT/UJI');

        Livewire::test(EditSurat::class, ['record' => $surat->getRouteKey()])
            ->fillForm(['no_surat' => '001/SURAT/UJI'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Nilai tetap sama dan tidak ada duplikasi/rollback yang tak diinginkan.
        $this->assertSame('001/SURAT/UJI', $surat->fresh()->no_surat);
        $this->assertDatabaseCount('surat', 1);
    }

    public function test_edit_ke_no_surat_duplikat_ditolak_oleh_validasi(): void
    {
        $this->actingAs($this->admin());
        $this->surat('001/SURAT/UJI');
        $target = $this->surat('002/SURAT/UJI');

        Livewire::test(EditSurat::class, ['record' => $target->getRouteKey()])
            ->fillForm(['no_surat' => '001/SURAT/UJI'])
            ->call('save')
            ->assertHasFormErrors(['no_surat']);

        // Record target tidak berubah (perubahan ditolak sebelum menyentuh DB).
        $this->assertSame('002/SURAT/UJI', $target->fresh()->no_surat);
    }

    public function test_buat_surat_dengan_no_surat_duplikat_ditolak_oleh_validasi(): void
    {
        $this->actingAs($this->admin());
        $this->surat('001/SURAT/UJI');

        $path = 'surat/file/' . Str::uuid() . '.pdf';
        Storage::disk(config('filesystems.private_disk', 'private'))
            ->put($path, '%PDF-1.4 berkas uji');

        Livewire::test(CreateSurat::class)
            ->fillForm([
                'status' => 'masuk',
                'no_surat' => '001/SURAT/UJI',
                'pengirim' => 'Dinas Uji',
                'tanggal_terima' => now()->toDateString(),
                'perihal' => 'Perihal uji',
                'file_surat' => [$path],
            ])
            ->call('create')
            ->assertHasFormErrors(['no_surat']);

        // Tidak ada record baru yang tersimpan.
        $this->assertDatabaseCount('surat', 1);
    }

    public function test_buat_surat_dengan_no_surat_baru_berhasil(): void
    {
        $this->actingAs($this->admin());
        $this->surat('001/SURAT/UJI');

        $path = 'surat/file/' . Str::uuid() . '.pdf';
        Storage::disk(config('filesystems.private_disk', 'private'))
            ->put($path, '%PDF-1.4 berkas uji');

        Livewire::test(CreateSurat::class)
            ->fillForm([
                'status' => 'masuk',
                'no_surat' => '003/SURAT/UJI',
                'pengirim' => 'Dinas Uji',
                'tanggal_terima' => now()->toDateString(),
                'perihal' => 'Perihal uji',
                'file_surat' => [$path],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('surat', ['no_surat' => '003/SURAT/UJI']);
    }
}
