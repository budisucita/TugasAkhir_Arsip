<?php

namespace App\Observers;

use App\Filament\Widgets\StatsOverview;
use App\Models\Log;
use App\Models\Surat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as SysLog;

class SuratObserver
{
    /**
     * Handle the Surat "created" event.
     */
    public function created(Surat $surat): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Surat::class,
                'record_id' => $surat->id,
                'action' => 'created',
                'description' => "Mencatat surat baru \"{$surat->no_surat}\" ({$surat->status}) dari {$surat->pengirim}",
                'after' => $surat->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log surat (created)', [
                'error' => $e->getMessage(),
                'surat_id' => $surat->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Surat "updated" event.
     */
    public function updated(Surat $surat): void
    {
        $this->clearCache();

        $perubahan = [];

        if ($surat->isDirty('no_surat')) {
            $perubahan[] = "Nomor ({$surat->getOriginal('no_surat')} -> {$surat->no_surat})";
        }

        if ($surat->isDirty('status')) {
            $perubahan[] = "Jenis ({$surat->getOriginal('status')} -> {$surat->status})";
        }

        if ($surat->isDirty('pengirim')) {
            $perubahan[] = "Pengirim ({$surat->getOriginal('pengirim')} -> {$surat->pengirim})";
        }

        if ($surat->isDirty('perihal')) {
            $perubahan[] = 'Perihal diperbarui';
        }

        if ($surat->isDirty('tanggal_terima')) {
            $perubahan[] = 'Tanggal terima diperbarui';
        }

        if ($surat->isDirty('file_surat')) {
            $perubahan[] = 'File surat diganti';
        }

        $deskripsi = empty($perubahan)
            ? "Memperbarui surat \"{$surat->no_surat}\" (tanpa perubahan signifikan)"
            : 'Mengubah surat "' . $surat->no_surat . '": ' . implode(', ', $perubahan);

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Surat::class,
                'record_id' => $surat->id,
                'action' => 'updated',
                'description' => $deskripsi,
                'before' => $surat->getOriginal(),
                'after' => $surat->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log surat (updated)', [
                'error' => $e->getMessage(),
                'surat_id' => $surat->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Surat "deleted" event.
     */
    public function deleted(Surat $surat): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Surat::class,
                'record_id' => $surat->id,
                'action' => 'deleted',
                'description' => "Menghapus surat \"{$surat->no_surat}\" (ID: {$surat->id})",
                'before' => $surat->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log surat (deleted)', [
                'error' => $e->getMessage(),
                'surat_id' => $surat->id ?? null,
            ]);
        }
    }

    /**
     * Bersihkan cache statistik Dashboard agar "Total Surat" selalu
     * menampilkan nilai terbaru setelah ada perubahan data surat.
     */
    private function clearCache(): void
    {
        Cache::forget(StatsOverview::CACHE_KEY);
    }
}
