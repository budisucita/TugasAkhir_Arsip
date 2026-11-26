<?php

namespace Tests\Feature;

use App\Filament\Resources\LogResource;
use App\Filament\Resources\LogResource\Pages\ListLogs;
use App\Models\Anggota;
use App\Models\Keuangan;
use App\Models\Log;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji perluasan audit log: pencatatan perubahan Surat, Anggota, dan
 * perubahan peran akun User, serta filter modul pada halaman Log Aktivitas.
 */
class AuditLogObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_surat_mencatat_log_pembuatan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $surat = $this->makeSurat('001/SM/UJI');

        $this->assertDatabaseHas('logs', [
            'model' => Surat::class,
            'record_id' => $surat->id,
            'action' => 'created',
            'user_id' => $admin->id,
        ]);

        $log = Log::where('model', Surat::class)->where('record_id', $surat->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('001/SM/UJI', (string) $log->description);
        $this->assertNotEmpty($log->after);
    }

    public function test_observer_surat_mencatat_log_perubahan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $surat = $this->makeSurat('002/SM/UJI');

        $surat->update(['no_surat' => '003/SM/UJI']);

        $log = Log::where('model', Surat::class)->where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Nomor', (string) $log->description);
    }

    public function test_observer_anggota_mencatat_log_pembuatan_dan_perubahan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $anggota = Anggota::create(['nama' => 'Budi', 'status' => 'aktif']);

        $this->assertDatabaseHas('logs', [
            'model' => Anggota::class,
            'record_id' => $anggota->id,
            'action' => 'created',
            'user_id' => $admin->id,
        ]);

        $anggota->update(['nama' => 'Budi Santoso']);

        $log = Log::where('model', Anggota::class)->where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Nama', (string) $log->description);
    }

    public function test_observer_user_mencatat_perubahan_peran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $target = User::factory()->create(['role' => 'pengurus']);

        // Bersihkan log pembuatan akun agar fokus pada perubahan peran.
        Log::where('model', User::class)->delete();

        $target->update(['role' => 'admin']);

        $log = Log::where('model', User::class)->where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('Peran', (string) $log->description);
        $this->assertStringContainsString('pengurus', (string) $log->description);
    }

    public function test_observer_user_tidak_mencatat_log_tanpa_perubahan_berarti(): void
    {
        User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'pengurus']);

        Log::where('model', User::class)->where('record_id', $target->id)->delete();

        // Menyimpan tanpa mengubah atribut apa pun tidak boleh membuat log baru.
        $target->save();

        $this->assertDatabaseMissing('logs', [
            'model' => User::class,
            'record_id' => $target->id,
            'action' => 'updated',
        ]);
    }

    public function test_menu_admin_berlabel_log_aktivitas(): void
    {
        $this->assertSame('Log Aktivitas', LogResource::getNavigationLabel());
    }

    public function test_filter_modul_memfilter_log_sesuai_kategori(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $logKeuangan = Log::create([
            'user_id' => $admin->id,
            'model' => Keuangan::class,
            'record_id' => 1,
            'action' => 'created',
            'description' => 'Log Keuangan',
        ]);

        $logSurat = Log::create([
            'user_id' => $admin->id,
            'model' => Surat::class,
            'record_id' => 2,
            'action' => 'created',
            'description' => 'Log Surat',
        ]);

        Livewire::test(ListLogs::class)
            ->filterTable('model', Keuangan::class)
            ->assertCanSeeTableRecords([$logKeuangan])
            ->assertCanNotSeeTableRecords([$logSurat]);
    }

    private function makeSurat(string $noSurat): Surat
    {
        return Surat::create([
            'no_surat' => $noSurat,
            'pengirim' => 'Dinas Uji',
            'tanggal_terima' => now()->toDateString(),
            'status' => 'masuk',
            'perihal' => 'Perihal uji',
            'file_surat' => 'surat/file/uji.pdf',
        ]);
    }
}
