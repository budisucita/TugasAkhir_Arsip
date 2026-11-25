<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Menguji penghapusan kolom hantu `views` dari model Kegiatan.
 *
 * Kolom `views` tidak pernah ada pada skema tabel `kegiatan`, sehingga
 * memasukkannya ke $fillable berpotensi menyesatkan dan memicu error bila
 * suatu saat dimass-assign.
 */
class KegiatanGhostColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_views_tidak_lagi_mass_assignable(): void
    {
        $this->assertNotContains('views', (new Kegiatan())->getFillable());
    }

    public function test_kolom_views_tidak_ada_pada_skema_database(): void
    {
        $this->assertFalse(
            Schema::hasColumn('kegiatan', 'views'),
            'Kolom `views` tidak boleh ada pada tabel kegiatan.'
        );
    }

    public function test_atribut_views_diabaikan_saat_pembuatan_kegiatan(): void
    {
        // Atribut 'views' bukan fillable dan bukan kolom, sehingga harus diabaikan
        // tanpa memicu error SQL "kolom tidak dikenal".
        $kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Uji Ghost Column',
            'status' => 'Direncanakan',
            'tanggal_mulai' => now()->toDateString(),
            'views' => 999,
        ]);

        $this->assertDatabaseHas('kegiatan', ['id' => $kegiatan->id]);
        $this->assertArrayNotHasKey('views', $kegiatan->getAttributes());
    }
}
