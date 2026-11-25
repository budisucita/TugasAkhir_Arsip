<?php

namespace App\Observers;

use App\Filament\Widgets\StatsOverview;
use App\Models\Kegiatan;
use App\Models\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as SysLog;
use Illuminate\Support\Facades\Auth;

class KegiatanObserver
{
    /**
     * Handle the Kegiatan "created" event.
     */
    public function created(Kegiatan $kegiatan): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Kegiatan::class,
                'record_id' => $kegiatan->id,
                'action' => 'created',
                'description' => "Mencatat kegiatan baru \"{$kegiatan->nama_kegiatan}\" (Status: {$kegiatan->status})",
                'after' => $kegiatan->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log kegiatan (created)', [
                'error' => $e->getMessage(),
                'kegiatan_id' => $kegiatan->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Kegiatan "updated" event.
     * Fokus utama: pemantauan perubahan status.
     */
    public function updated(Kegiatan $kegiatan): void
    {
        $this->clearCache();

        $perubahan = [];

        // 1. Perubahan STATUS (alur kerja utama)
        if ($kegiatan->isDirty('status')) {
            $oldStatus = $kegiatan->getOriginal('status');
            $newStatus = $kegiatan->status;
            $perubahan[] = "Status ({$oldStatus} -> {$newStatus})";
        }

        // 2. Perubahan NAMA KEGIATAN
        if ($kegiatan->isDirty('nama_kegiatan')) {
            $oldName = $kegiatan->getOriginal('nama_kegiatan');
            $perubahan[] = "Nama ({$oldName} -> {$kegiatan->nama_kegiatan})";
        }

        // 3. Perubahan JADWAL
        if ($kegiatan->isDirty('tanggal_mulai') || $kegiatan->isDirty('tanggal_selesai')) {
            $perubahan[] = 'Jadwal kegiatan diperbarui';
        }

        if (empty($perubahan)) {
            $deskripsi = "Memperbarui kegiatan \"{$kegiatan->nama_kegiatan}\" (tanpa perubahan status)";
        } else {
            $deskripsi = "Mengubah kegiatan \"{$kegiatan->nama_kegiatan}\": " . implode(', ', $perubahan);
        }

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Kegiatan::class,
                'record_id' => $kegiatan->id,
                'action' => 'updated',
                'description' => $deskripsi,
                'before' => $kegiatan->getOriginal(),
                'after' => $kegiatan->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log kegiatan (updated)', [
                'error' => $e->getMessage(),
                'kegiatan_id' => $kegiatan->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Kegiatan "deleted" event.
     */
    public function deleted(Kegiatan $kegiatan): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Kegiatan::class,
                'record_id' => $kegiatan->id,
                'action' => 'deleted',
                'description' => "Menghapus kegiatan \"{$kegiatan->nama_kegiatan}\" (ID: {$kegiatan->id})",
                'before' => $kegiatan->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log kegiatan (deleted)', [
                'error' => $e->getMessage(),
                'kegiatan_id' => $kegiatan->id ?? null,
            ]);
        }
    }

    /**
     * Bersihkan cache landing page dan statistik Dashboard yang terpengaruh
     * perubahan kegiatan (mis. jumlah "Kegiatan Aktif").
     */
    private function clearCache(): void
    {
        Cache::forget('kegiatan_terbaru_landing');
        Cache::forget('statistik_landing');
        Cache::forget(StatsOverview::CACHE_KEY);
    }
}
