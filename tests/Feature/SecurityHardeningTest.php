<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Menguji pengerasan keamanan kredensial dan konfigurasi proxy.
 *
 * Skenario:
 * - UserSeeder tidak membuat akun demo berkata sandi default di produksi.
 * - UserSeeder tetap membuat akun demo di lingkungan pengembangan/pengujian.
 * - bootstrap/app.php tidak lagi mempercayai semua proxy ('*').
 * - Klien dari IP tak terpercaya tidak dapat memalsukan alamat IP lewat header.
 * - Proxy internal tetap dapat meneruskan IP asli klien.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_melewatkan_kata_sandi_default_di_produksi(): void
    {
        $this->app['env'] = 'production';

        (new UserSeeder())->run();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_seeder_membuat_akun_demo_di_lingkungan_pengujian(): void
    {
        // Default environment pengujian adalah 'testing' (diizinkan).
        (new UserSeeder())->run();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@dharmabhakti.com',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'pengurus@dharmabhakti.com',
            'role' => 'pengurus',
        ]);
    }

    public function test_bootstrap_tidak_lagi_mempercayai_semua_proxy(): void
    {
        $contents = File::get(base_path('bootstrap/app.php'));

        $this->assertStringNotContainsString("trustProxies(at: '*')", $contents);
        // Daftar proxy eksplisit (loopback) tersedia sebagai gantinya.
        $this->assertStringContainsString('127.0.0.1', $contents);
    }

    public function test_klien_tidak_dipercaya_tidak_dapat_memalsukan_alamat_ip(): void
    {
        Route::middleware('web')->get('/_uji-ip', fn (Request $request) => $request->ip());

        $response = $this->call('GET', '/_uji-ip', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',       // IP publik: tidak dipercaya
            'HTTP_X_FORWARDED_FOR' => '10.9.9.9',  // Upaya pemalsuan IP
        ]);

        $this->assertSame('203.0.113.10', $response->getContent());
    }

    public function test_proxy_internal_dipercaya_meneruskan_ip_klien_asli(): void
    {
        Route::middleware('web')->get('/_uji-ip', fn (Request $request) => $request->ip());

        $response = $this->call('GET', '/_uji-ip', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',          // Proxy internal: dipercaya
            'HTTP_X_FORWARDED_FOR' => '203.0.113.99',
        ]);

        $this->assertSame('203.0.113.99', $response->getContent());
    }
}
