<?php

namespace App\Observers;

use App\Filament\Widgets\StatsOverview;
use App\Models\Surat;
use Illuminate\Support\Facades\Cache;

class SuratObserver
{
    /**
     * Handle the Surat "created" event.
     */
    public function created(Surat $surat): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Surat "updated" event.
     */
    public function updated(Surat $surat): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Surat "deleted" event.
     */
    public function deleted(Surat $surat): void
    {
        $this->clearCache();
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
