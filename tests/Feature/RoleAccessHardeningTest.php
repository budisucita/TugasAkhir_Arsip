<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji pengerasan kebijakan akses peran (RBAC) dan penutupan celah keamanan:
 *
 * - Proteksi audit trail: raw JSON kolom before/after pada halaman Log hanya
 *   dapat dibaca oleh admin; peran pengurus hanya melihat metadata log.
 * - Proteksi akun: admin DILARANG menghapus akun yang sedang digunakan untuk
 *   login (baik lewat Policy maupun lewat penghapusan langsung/massal).
 */
class RoleAccessHardeningTest extends TestCase
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

    private function makeLogWithRawData(User $actor): Log
    {
        return Log::create([
            'user_id' => $actor->id,
            'action' => 'updated',
            'description' => 'Uji audit trail',
            'before' => ['keterangan' => 'NILAI_RAHASIA_LAMA'],
            'after' => ['keterangan' => 'NILAI_RAHASIA_BARU'],
        ]);
    }

    // ---------------------------------------------------------------------
    // Proteksi raw data (before/after) pada halaman Log
    // ---------------------------------------------------------------------

    public function test_pengurus_tidak_dapat_membaca_raw_json_before_after_pada_log(): void
    {
        $log = $this->makeLogWithRawData($this->admin());

        $response = $this->actingAs($this->pengurus())
            ->get('/admin/logs/'.$log->getKey());

        // Halaman log tetap dapat dibuka (metadata saja) ...
        $response->assertOk();

        // ... tetapi data mentah before/after tidak boleh muncul di respons.
        $response->assertDontSee('NILAI_RAHASIA_LAMA');
        $response->assertDontSee('NILAI_RAHASIA_BARU');
        $response->assertDontSee('Data Lama (Before)');
        $response->assertDontSee('Data Baru (After)');
    }

    public function test_admin_dapat_membaca_raw_json_before_after_pada_log(): void
    {
        $log = $this->makeLogWithRawData($this->admin());

        $response = $this->actingAs($this->admin())
            ->get('/admin/logs/'.$log->getKey());

        $response->assertOk();
        $response->assertSee('NILAI_RAHASIA_LAMA');
        $response->assertSee('NILAI_RAHASIA_BARU');
    }

    // ---------------------------------------------------------------------
    // Proteksi akun sendiri (self-deletion)
    // ---------------------------------------------------------------------

    public function test_policy_menolak_admin_menghapus_akun_sendiri(): void
    {
        $admin = $this->admin();

        $this->assertFalse(
            Gate::forUser($admin)->allows('delete', $admin),
            'Admin seharusnya tidak diizinkan menghapus akunnya sendiri.'
        );
    }

    public function test_policy_mengizinkan_admin_menghapus_akun_lain(): void
    {
        $admin = $this->admin();
        $lain = $this->pengurus();

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $lain));
    }

    public function test_policy_menolak_pengurus_menghapus_akun_apapun(): void
    {
        $pengurus = $this->pengurus();
        $lain = User::factory()->create(['role' => 'pengurus']);

        $this->assertFalse(Gate::forUser($pengurus)->allows('delete', $lain));
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri_lewat_jalur_langsung(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        // Guard model mencegah penghapusan, bahkan bila Policy dilewati
        // (mis. aksi hapus massal Filament yang memakai ability deleteAny).
        $this->assertFalse($admin->delete());
        $this->assertDatabaseHas('users', ['id' => $admin->getKey()]);
    }

    public function test_admin_tetap_dapat_menghapus_akun_pengguna_lain(): void
    {
        $admin = $this->admin();
        $lain = $this->pengurus();
        $this->actingAs($admin);

        $this->assertTrue($lain->delete());
        $this->assertDatabaseMissing('users', ['id' => $lain->getKey()]);
    }

    public function test_guard_model_menolak_penghapusan_akun_admin_lain(): void
    {
        $admin = $this->admin();
        $adminLain = $this->admin();
        $this->actingAs($admin);

        // Akun berperan admin dilindungi mutlak, termasuk dari admin lain dan
        // bahkan bila Policy dilewati (mis. jalur hapus massal Filament).
        $this->assertFalse($adminLain->delete());
        $this->assertDatabaseHas('users', ['id' => $adminLain->getKey()]);
    }

    // ---------------------------------------------------------------------
    // Proteksi akun admin lewat fitur hapus massal (bulk delete)
    // ---------------------------------------------------------------------

    public function test_bulk_delete_tidak_menghapus_akun_admin_yang_dipilih(): void
    {
        $admin = $this->admin();
        $adminLain = $this->admin();

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$adminLain]);

        $this->assertDatabaseHas('users', ['id' => $adminLain->getKey()]);
    }

    public function test_bulk_delete_akun_sendiri_tidak_terhapus(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$admin]);

        $this->assertDatabaseHas('users', ['id' => $admin->getKey()]);
    }

    public function test_bulk_delete_akun_admin_menampilkan_pesan_error(): void
    {
        $admin = $this->admin();
        $adminLain = $this->admin();

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$adminLain])
            ->assertNotified('Akun admin tidak dapat dihapus');
    }

    public function test_bulk_delete_akun_non_admin_tetap_berfungsi(): void
    {
        $admin = $this->admin();
        $pengurusSatu = $this->pengurus();
        $pengurusDua = $this->pengurus();

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$pengurusSatu, $pengurusDua]);

        $this->assertDatabaseMissing('users', ['id' => $pengurusSatu->getKey()]);
        $this->assertDatabaseMissing('users', ['id' => $pengurusDua->getKey()]);
    }

    public function test_bulk_delete_campuran_hanya_menghapus_akun_non_admin(): void
    {
        $admin = $this->admin();
        $adminLain = $this->admin();
        $pengurus = $this->pengurus();

        Livewire::actingAs($admin);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$adminLain, $pengurus]);

        // Akun admin dilindungi, akun pengurus tetap terhapus.
        $this->assertDatabaseHas('users', ['id' => $adminLain->getKey()]);
        $this->assertDatabaseMissing('users', ['id' => $pengurus->getKey()]);
    }
}
