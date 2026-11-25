<?php

namespace App\Observers;

use App\Filament\Widgets\StatsOverview;
use App\Models\Anggota;
use Illuminate\Support\Facades\Cache;

class AnggotaObserver
{
    /**
     * Handle the Anggota "created" event.
     */
    public function created(Anggota $anggota): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Anggota "updated" event.
     */
    public function updated(Anggota $anggota): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Anggota "deleted" event.
     */
    public function deleted(Anggota $anggota): void
    {
        $this->clearCache();
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
