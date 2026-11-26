<?php

namespace Tests\Feature;

use App\Filament\Pages\PengaturanWebsite;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji struktur jabatan (disimpan di tabel settings) dan pengisian otomatis
 * blok tanda tangan pada laporan keuangan.
 */
class JabatanPenandatanganTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_menampilkan_nama_penandatangan_dari_pengaturan(): void
    {
        Setting::create(['key' => 'nama_ketua', 'value' => 'I Made Ketua']);
        Setting::create(['key' => 'nama_bendahara', 'value' => 'I Wayan Bendahara']);
        Setting::create(['key' => 'nama_kepala_lingkungan', 'value' => 'I Nyoman Kelian']);
        Setting::create(['key' => 'periode_jabatan', 'value' => '2024 - 2027']);

        // Buang cache 'settings' agar view composer memuat data terbaru.
        Cache::forget('settings');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/laporan/keuangan/print')
            ->assertOk()
            ->assertSee('I Made Ketua')
            ->assertSee('I Wayan Bendahara')
            ->assertSee('I Nyoman Kelian')
            ->assertSee('Periode Jabatan: 2024 - 2027');
    }

    public function test_laporan_tetap_menampilkan_titik_bila_jabatan_kosong(): void
    {
        Cache::forget('settings');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/laporan/keuangan/print')
            ->assertOk()
            ->assertSee('Hormat kami,')
            ->assertSee('Bendahara,');
    }

    public function test_halaman_pengaturan_menyimpan_struktur_jabatan(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(PengaturanWebsite::class)
            ->fillForm([
                'nama_ketua' => 'I Made Ketua',
                'nama_sekretaris' => 'Ni Wayan Sekretaris',
                'nama_bendahara' => 'I Gede Bendahara',
                'nama_kepala_lingkungan' => 'I Nyoman Kelian',
                'periode_jabatan' => '2024 - 2027',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', ['key' => 'nama_ketua', 'value' => 'I Made Ketua']);
        $this->assertDatabaseHas('settings', ['key' => 'periode_jabatan', 'value' => '2024 - 2027']);
    }
}
