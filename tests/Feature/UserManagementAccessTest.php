<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Menguji keamanan manajemen pengguna (prefix /admin/users) pada dua lapisan:
 * - Lapisan Policy (UserPolicy) → pertahanan utama.
 * - Lapisan middleware rute (RestrictUserResourceForPengurus) → mencegah bypass.
 *
 * Verifikasi:
 * - Admin dapat mengakses seluruh fitur /admin/users.
 * - Pengurus ditolak 403 pada seluruh rute /admin/users.
 * - Tidak ada kebocoran informasi sensitif (email) ke peran pengurus.
 */
class UserManagementAccessTest extends TestCase
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

    public function test_admin_dapat_mengakses_seluruh_fitur_manajemen_pengguna(): void
    {
        $target = User::factory()->create(['role' => 'pengurus']);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/users/create')->assertOk();
        $this->actingAs($admin)->get('/admin/users/'.$target->getKey().'/edit')->assertOk();

        // Kontrol positif: admin memang melihat email pengguna lain.
        $this->actingAs($admin)->get('/admin/users')->assertSee($target->email);
    }

    public function test_pengurus_ditolak_403_pada_seluruh_rute_manajemen_pengguna(): void
    {
        $target = User::factory()->create(['role' => 'pengurus']);
        $pengurus = $this->pengurus();

        foreach ([
            '/admin/users',
            '/admin/users/create',
            '/admin/users/'.$target->getKey().'/edit',
        ] as $url) {
            $this->actingAs($pengurus)->get($url)->assertForbidden();
        }
    }

    public function test_pengurus_tidak_dapat_membocorkan_email_lewat_endpoint_manapun(): void
    {
        $target = User::factory()->create(['role' => 'pengurus']);
        $pengurus = $this->pengurus();

        // Halaman HTML (browser).
        $html = $this->actingAs($pengurus)->get('/admin/users');
        $html->assertForbidden();
        $html->assertDontSee($target->email);

        // Permintaan JSON (potensi bypass lewat header Accept).
        $json = $this->actingAs($pengurus)->getJson('/admin/users');
        $json->assertForbidden();
        $this->assertStringNotContainsString($target->email, $json->getContent());

        // Endpoint detail/edit.
        $this->actingAs($pengurus)
            ->get('/admin/users/'.$target->getKey().'/edit')
            ->assertForbidden()
            ->assertDontSee($target->email);
    }

    public function test_policy_menolak_pengurus_dan_mengizinkan_admin(): void
    {
        $pengurus = $this->pengurus();
        $admin = $this->admin();

        // Lapisan otorisasi (Policy).
        $this->assertFalse(Gate::forUser($pengurus)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($pengurus)->allows('view', $admin));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $pengurus));
    }
}
