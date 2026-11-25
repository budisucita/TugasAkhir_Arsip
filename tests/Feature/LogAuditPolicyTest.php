<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Menguji integritas audit trail (Log bersifat append-only / permanen).
 *
 * Verifikasi:
 * - Tidak ada pengguna (termasuk admin) yang dapat membuat/mengubah/menghapus
 *   entri log secara manual.
 * - Endpoint penghapusan (delete/deleteAny/forceDelete/forceDeleteAny) ditolak.
 * - Log tetap dapat dibaca, dan pembuatan log otomatis oleh sistem (Observer)
 *   tetap berfungsi sehingga alur kerja audit tidak rusak.
 */
class LogAuditPolicyTest extends TestCase
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

    private function makeLog(User $user, string $action = 'created'): Log
    {
        // Dibuat langsung agar bypass policy (mensimulasikan penulisan oleh sistem).
        return Log::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => 'Entri log uji',
        ]);
    }

    public function test_tidak_ada_pengguna_yang_dapat_membuat_log_manual(): void
    {
        foreach ([$this->admin(), $this->pengurus()] as $user) {
            $this->assertTrue(
                Gate::forUser($user)->denies('create', Log::class),
                "create seharusnya ditolak untuk peran {$user->role}"
            );
        }
    }

    public function test_tidak_ada_pengguna_yang_dapat_mengubah_log(): void
    {
        $log = $this->makeLog($this->admin());

        foreach ([$this->admin(), $this->pengurus()] as $user) {
            $this->assertTrue(
                Gate::forUser($user)->denies('update', $log),
                "update seharusnya ditolak untuk peran {$user->role}"
            );
        }
    }

    public function test_semua_operasi_penghapusan_ditolak_untuk_admin_dan_pengurus(): void
    {
        $log = $this->makeLog($this->admin());

        foreach ([$this->admin(), $this->pengurus()] as $user) {
            foreach (['delete', 'deleteAny', 'forceDelete', 'forceDeleteAny'] as $ability) {
                $this->assertTrue(
                    Gate::forUser($user)->denies($ability, $log),
                    "{$ability} seharusnya ditolak untuk peran {$user->role}"
                );
            }
        }
    }

    public function test_seluruh_operasi_tulis_lainnya_juga_diblokir(): void
    {
        $log = $this->makeLog($this->admin());

        foreach ([$this->admin(), $this->pengurus()] as $user) {
            foreach (['restore', 'restoreAny', 'replicate', 'reorder'] as $ability) {
                $this->assertTrue(
                    Gate::forUser($user)->denies($ability, $log),
                    "{$ability} seharusnya ditolak untuk peran {$user->role}"
                );
            }
        }
    }

    public function test_admin_dan_pengurus_tetap_dapat_melihat_log(): void
    {
        $log = $this->makeLog($this->admin());

        $this->actingAs($this->admin())->get('/admin/logs')->assertOk();
        $this->actingAs($this->admin())->get('/admin/logs/'.$log->getKey())->assertOk();
        $this->actingAs($this->pengurus())->get('/admin/logs')->assertOk();
        $this->actingAs($this->pengurus())->get('/admin/logs/'.$log->getKey())->assertOk();
    }

    public function test_halaman_pembuatan_log_tidak_tersedia(): void
    {
        $this->assertFalse(\App\Filament\Resources\LogResource::hasPage('create'));
        $this->actingAs($this->admin())->get('/admin/logs/create')->assertNotFound();
    }

    public function test_pembuatan_log_otomatis_oleh_sistem_tetap_berfungsi(): void
    {
        // Observer menulis log langsung (bypass policy) saat data keuangan dibuat.
        Keuangan::create([
            'jenis_transaksi' => 'pengeluaran',
            'jumlah' => 5000,
            'keterangan' => 'Uji audit trail otomatis',
            'tanggal' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('logs', [
            'action' => 'created',
            'model' => Keuangan::class,
        ]);
    }
}
