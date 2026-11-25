<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Dokumentasi;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Log;
use App\Models\Surat;
use App\Models\TemplateSurat;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menguji matriks izin RBAC pada seluruh Policy.
 *
 * Aturan:
 * - Peran "pengurus" hanya boleh melihat (viewAny/view) untuk model arsip;
 *   peran "admin" boleh melakukan seluruh operasi CRUD.
 * - Model khusus admin (User) ditolak sepenuhnya untuk peran pengurus.
 * - Model read-only (Log) boleh dilihat semua peran, namun tidak boleh
 *   dibuat/diubah/dihapus oleh siapapun (audit trail permanen).
 */
class PolicyAccessTest extends TestCase
{
    public static function modelProvider(): array
    {
        return [
            'Kegiatan' => [Kegiatan::class],
            'Keuangan' => [Keuangan::class],
            'Anggota' => [Anggota::class],
            'Surat' => [Surat::class],
            'Dokumentasi' => [Dokumentasi::class],
            'TemplateSurat' => [TemplateSurat::class],
        ];
    }

    public static function adminOnlyModelProvider(): array
    {
        return [
            'User' => [User::class],
        ];
    }

    public static function readOnlyModelProvider(): array
    {
        return [
            'Log' => [Log::class],
        ];
    }

    #[DataProvider('modelProvider')]
    #[DataProvider('adminOnlyModelProvider')]
    public function test_admin_diizinkan_seluruh_operasi_crud(string $model): void
    {
        $admin = new User(['role' => 'admin']);
        $record = new $model();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', $model), "viewAny ditolak untuk {$model}");
        $this->assertTrue(Gate::forUser($admin)->allows('view', $record), "view ditolak untuk {$model}");
        $this->assertTrue(Gate::forUser($admin)->allows('create', $model), "create ditolak untuk {$model}");
        $this->assertTrue(Gate::forUser($admin)->allows('update', $record), "update ditolak untuk {$model}");
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $record), "delete ditolak untuk {$model}");
        $this->assertTrue(Gate::forUser($admin)->allows('deleteAny', $model), "deleteAny ditolak untuk {$model}");
    }

    #[DataProvider('modelProvider')]
    public function test_pengurus_hanya_diizinkan_melihat(string $model): void
    {
        $pengurus = new User(['role' => 'pengurus']);
        $record = new $model();

        $this->assertTrue(Gate::forUser($pengurus)->allows('viewAny', $model), "viewAny ditolak untuk {$model}");
        $this->assertTrue(Gate::forUser($pengurus)->allows('view', $record), "view ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('create', $model), "create seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('update', $record), "update seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('delete', $record), "delete seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('deleteAny', $model), "deleteAny seharusnya ditolak untuk {$model}");
    }

    #[DataProvider('adminOnlyModelProvider')]
    public function test_pengurus_ditolak_sepenuhnya_pada_model_khusus_admin(string $model): void
    {
        $pengurus = new User(['role' => 'pengurus']);
        $record = new $model();

        $this->assertFalse(Gate::forUser($pengurus)->allows('viewAny', $model), "viewAny seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('view', $record), "view seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('create', $model), "create seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('update', $record), "update seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('delete', $record), "delete seharusnya ditolak untuk {$model}");
        $this->assertFalse(Gate::forUser($pengurus)->allows('deleteAny', $model), "deleteAny seharusnya ditolak untuk {$model}");
    }

    #[DataProvider('readOnlyModelProvider')]
    public function test_model_read_only_tidak_dapat_dimodifikasi_oleh_siapapun(string $model): void
    {
        $record = new $model();

        foreach (['admin', 'pengurus'] as $role) {
            $user = new User(['role' => $role]);

            // Baca tetap diizinkan.
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', $model), "viewAny ditolak untuk {$role} {$model}");
            $this->assertTrue(Gate::forUser($user)->allows('view', $record), "view ditolak untuk {$role} {$model}");

            // Seluruh operasi tulis dihapus permanen.
            foreach (['create', 'update', 'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny', 'replicate', 'reorder'] as $ability) {
                $this->assertFalse(
                    Gate::forUser($user)->allows($ability, $record),
                    "{$ability} seharusnya ditolak untuk {$role} {$model}"
                );
            }
        }
    }

    public function test_admin_diizinkan_seluruh_operasi_pada_halaman_pengaturan_website(): void
    {
        $this->actingAs(new User(['role' => 'admin']));
        $this->assertTrue(\App\Filament\Pages\PengaturanWebsite::canAccess());
    }

    public function test_pengurus_ditolak_pada_halaman_pengaturan_website(): void
    {
        $this->actingAs(new User(['role' => 'pengurus']));
        $this->assertFalse(\App\Filament\Pages\PengaturanWebsite::canAccess());
    }
}
