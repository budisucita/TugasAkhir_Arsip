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
 * Aturan: peran "pengurus" hanya boleh melihat (viewAny/view),
 * sedangkan peran "admin" boleh melakukan seluruh operasi CRUD.
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
            'Log' => [Log::class],
            'User' => [User::class],
        ];
    }

    #[DataProvider('modelProvider')]
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
