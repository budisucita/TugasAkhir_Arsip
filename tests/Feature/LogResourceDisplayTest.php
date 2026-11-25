<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji tampilan log aktivitas (LogResource) agar tidak salah melabeli.
 *
 * Fokus:
 * - Kolom "Objek" (model) dan "Deskripsi" ditampilkan eksplisit.
 * - Log kegiatan operasional tidak lagi dilabeli "Data Keuangan Terhapus".
 * - Label aksi mengikuti jenis aksi yang dilakukan.
 */
class LogResourceDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function log(array $attributes = []): Log
    {
        return Log::create(array_merge([
            'user_id' => $this->admin()->id,
            'action' => 'created',
            'description' => 'Mencatat kegiatan baru "Rapat Rutin" (Status: Direncanakan)',
        ], $attributes));
    }

    public function test_tabel_log_menampilkan_kolom_objek_dan_deskripsi(): void
    {
        $this->log(['model' => Kegiatan::class, 'record_id' => 1]);

        $this->actingAs($this->admin())
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee('Objek')
            ->assertSee('Deskripsi')
            ->assertSee('Kegiatan')
            ->assertSee('Rapat Rutin');
    }

    public function test_detail_log_kegiatan_tidak_dilabeli_data_keuangan_terhapus(): void
    {
        $log = $this->log(['model' => Kegiatan::class, 'record_id' => 1]);

        $this->actingAs($this->admin())
            ->get('/admin/logs/' . $log->getKey())
            ->assertOk()
            ->assertSee('Objek')
            ->assertSee('Deskripsi')
            ->assertSee('Kegiatan')
            ->assertSee('Mencatat kegiatan baru')
            // Placeholder lama yang menyesatkan tidak boleh muncul lagi.
            ->assertDontSee('Data Keuangan Terhapus');
    }

    public function test_detail_log_keuangan_menampilkan_objek_keuangan(): void
    {
        $log = $this->log([
            'model' => Keuangan::class,
            'record_id' => 1,
            'description' => 'Mencatat pemasukan baru sejumlah Rp 10.000',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/logs/' . $log->getKey())
            ->assertOk()
            ->assertSee('Keuangan')
            ->assertSee('Mencatat pemasukan baru sejumlah Rp 10.000');
    }

    public function test_label_aksi_mengikuti_jenis_aksi(): void
    {
        // Detail log tidak memuat dropdown filter, sehingga label yang muncul
        // benar-benar berasal dari badge aksi (bukan opsi filter).
        $log = $this->log(['action' => 'deleted', 'description' => 'Log dihapus']);

        $this->actingAs($this->admin())
            ->get('/admin/logs/' . $log->getKey())
            ->assertOk()
            ->assertSee('Dihapus');
    }
}
