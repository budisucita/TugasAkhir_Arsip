<?php

namespace App\Observers;

use App\Filament\Widgets\StatsOverview;
use App\Models\Anggota;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as SysLog;

class AnggotaObserver
{
    /**
     * Handle the Anggota "created" event.
     */
    public function created(Anggota $anggota): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Anggota::class,
                'record_id' => $anggota->id,
                'action' => 'created',
                'description' => "Menambahkan anggota baru \"{$anggota->nama}\" (Status: {$anggota->status})",
                'after' => $anggota->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log anggota (created)', [
                'error' => $e->getMessage(),
                'anggota_id' => $anggota->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Anggota "updated" event.
     */
    public function updated(Anggota $anggota): void
    {
        $this->clearCache();

        $perubahan = [];

        if ($anggota->isDirty('nama')) {
            $perubahan[] = "Nama ({$anggota->getOriginal('nama')} -> {$anggota->nama})";
        }

        if ($anggota->isDirty('status')) {
            $perubahan[] = "Status ({$anggota->getOriginal('status')} -> {$anggota->status})";
        }

        if ($anggota->isDirty('kontak')) {
            $perubahan[] = 'Kontak diperbarui';
        }

        if ($anggota->isDirty('alamat')) {
            $perubahan[] = 'Alamat diperbarui';
        }

        if ($anggota->isDirty('foto')) {
            $perubahan[] = 'Foto anggota diganti';
        }

        $deskripsi = empty($perubahan)
            ? "Memperbarui anggota \"{$anggota->nama}\" (tanpa perubahan signifikan)"
            : 'Mengubah anggota "' . $anggota->nama . '": ' . implode(', ', $perubahan);

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Anggota::class,
                'record_id' => $anggota->id,
                'action' => 'updated',
                'description' => $deskripsi,
                'before' => $anggota->getOriginal(),
                'after' => $anggota->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log anggota (updated)', [
                'error' => $e->getMessage(),
                'anggota_id' => $anggota->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Anggota "deleted" event.
     */
    public function deleted(Anggota $anggota): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => Anggota::class,
                'record_id' => $anggota->id,
                'action' => 'deleted',
                'description' => "Menghapus anggota \"{$anggota->nama}\" (ID: {$anggota->id})",
                'before' => $anggota->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log anggota (deleted)', [
                'error' => $e->getMessage(),
                'anggota_id' => $anggota->id ?? null,
            ]);
        }
    }

    /**
     * Bersihkan cache statistik Dashboard agar "Total Anggota" selalu
     * menampilkan nilai terbaru setelah ada perubahan data anggota.
     */
    private function clearCache(): void
    {
        Cache::forget(StatsOverview::CACHE_KEY);
    }
}
