<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji kontrol akses RBAC pada panel Filament (level HTTP).
 *
 * Skenario yang diverifikasi:
 * - Tamu diarahkan ke halaman login.
 * - Admin dapat mengakses seluruh resource dan halaman pengaturan.
 * - Pengurus hanya dapat melihat daftar data, tidak dapat membuat/mengubah data
 *   maupun mengakses halaman pengaturan dan manajemen pengguna.
 */
class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pengurus(): User
    {
        return User::factory()->create(['role' => 'pengurus']);
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_halaman_login_dapat_diakses(): void
    {
        $this->get('/admin/login')->assertSuccessful();
    }

    public function test_admin_dan_pengurus_dapat_mengakses_dashboard(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertSuccessful();
        $this->actingAs($this->pengurus())->get('/admin')->assertSuccessful();
    }

    public function test_admin_dapat_mengakses_dan_membuat_semua_resource(): void
    {
        $this->actingAs($this->admin());

        foreach ([
            '/admin/kegiatans',
            '/admin/kegiatans/create',
            '/admin/keuangans',
            '/admin/keuangans/create',
            '/admin/anggotas',
            '/admin/anggotas/create',
            '/admin/surats',
            '/admin/surats/create',
            '/admin/dokumentasis',
            '/admin/dokumentasis/create',
            '/admin/template-surats',
            '/admin/template-surats/create',
            '/admin/users',
            '/admin/users/create',
            '/admin/logs',
            '/admin/pengaturan-website',
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_pengurus_dapat_melihat_tetapi_tidak_dapat_membuat_resource(): void
    {
        $this->actingAs($this->pengurus());

        // Halaman daftar (index) diizinkan untuk pengurus.
        foreach ([
            '/admin/kegiatans',
            '/admin/keuangans',
            '/admin/anggotas',
            '/admin/surats',
            '/admin/dokumentasis',
            '/admin/template-surats',
            '/admin/logs',
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }

        // Halaman pembuatan data ditolak untuk pengurus.
        foreach ([
            '/admin/kegiatans/create',
            '/admin/keuangans/create',
            '/admin/anggotas/create',
            '/admin/surats/create',
            '/admin/dokumentasis/create',
            '/admin/template-surats/create',
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_pengurus_ditolak_mengakses_halaman_pengaturan_website(): void
    {
        $this->actingAs($this->pengurus())
            ->get('/admin/pengaturan-website')
            ->assertForbidden();
    }

    public function test_pengurus_diblokir_dari_manajemen_pengguna(): void
    {
        $pengurus = $this->pengurus();
        $target = User::factory()->create(['role' => 'pengurus']);

        $this->actingAs($pengurus)->get('/admin/users')->assertSuccessful();
        $this->actingAs($pengurus)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($pengurus)->get('/admin/users/' . $target->getKey() . '/edit')->assertForbidden();
    }

    public function test_admin_dapat_membuka_form_ubah_data(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        $this->actingAs($this->admin())
            ->get('/admin/kegiatans/' . $kegiatan->getKey() . '/edit')
            ->assertSuccessful();
    }

    public function test_pengurus_ditolak_membuka_form_ubah_data(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        $this->actingAs($this->pengurus())
            ->get('/admin/kegiatans/' . $kegiatan->getKey() . '/edit')
            ->assertForbidden();
    }

    public function test_admin_dapat_membuka_detail_log(): void
    {
        $log = \App\Models\Log::create([
            'user_id' => $this->admin()->id,
            'action' => 'created',
            'description' => 'Uji akses log',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/logs/' . $log->getKey())
            ->assertSuccessful();
    }
}
