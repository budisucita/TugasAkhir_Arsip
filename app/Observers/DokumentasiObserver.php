<?php

namespace App\Observers;

use App\Models\Dokumentasi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DokumentasiObserver
{
    /**
     * Handle the Dokumentasi "created" event.
     */
    public function created(Dokumentasi $dokumentasi): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Dokumentasi "updated" event.
     */
    public function updated(Dokumentasi $dokumentasi): void
    {
        $this->clearCache();
        
        // Jika file diganti, hapus file lama (logika ini sering ditangani Filament, tapi bisa ditambahkan jika perlu)
        if ($dokumentasi->isDirty('file_path')) {
            $originalPath = $dokumentasi->getOriginal('file_path');
            if ($originalPath && Storage::disk('public')->exists($originalPath)) {
                Storage::disk('public')->delete($originalPath);
            }
        }
    }

    /**
     * Handle the Dokumentasi "deleted" event.
     */
    public function deleted(Dokumentasi $dokumentasi): void
    {
        $this->clearCache();
        
        // Pastikan file fisik terhapus
        if ($dokumentasi->file_path && Storage::disk('public')->exists($dokumentasi->file_path)) {
            try {
                Storage::disk('public')->delete($dokumentasi->file_path);
                Log::info("File dokumentasi dihapus: " . $dokumentasi->file_path);
            } catch (\Exception $e) {
                Log::error("Gagal menghapus file dokumentasi: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Dokumentasi "restored" event.
     */
    public function restored(Dokumentasi $dokumentasi): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Dokumentasi "force deleted" event.
     */
    public function forceDeleted(Dokumentasi $dokumentasi): void
    {
        $this->clearCache();
        
        if ($dokumentasi->file_path && Storage::disk('public')->exists($dokumentasi->file_path)) {
            Storage::disk('public')->delete($dokumentasi->file_path);
        }
    }

    /**
     * Clear relevant caches.
     */
    private function clearCache(): void
    {
        Cache::forget('dokumentasi_terbaru_landing');
        Cache::forget('statistik_landing');
        Cache::forget('kegiatan_terbaru_landing'); // Just in case thumbail kegiatan diambil dari dokumentasi
        
        Log::info('Cache landing page dibersihkan karena perubahan data dokumentasi.');
    }
}
