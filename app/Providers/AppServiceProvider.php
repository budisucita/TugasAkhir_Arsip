<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Keuangan;
use App\Observers\KeuanganObserver;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache; // <-- Import Cache
use Illuminate\Support\Facades\View;   // <-- Import View

use App\Models\Dokumentasi;
use App\Observers\DokumentasiObserver;

use App\Models\Kegiatan;
use App\Observers\KegiatanObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    
    public function boot(): void
    {
        Keuangan::observe(KeuanganObserver::class);
        Dokumentasi::observe(DokumentasiObserver::class);
        Kegiatan::observe(KegiatanObserver::class);

        // Coba ambil settings dari cache terlebih dahulu.
        // Jika tidak ada, ambil dari database, ubah menjadi collection,
        // dan simpan di cache selamanya.
        try {
            $settings = Cache::rememberForever('settings', function () {
                // Kita bungkus dengan collect() untuk mengubah array menjadi Laravel Collection
                return collect(Setting::pluck('value', 'key'));
            });
        } catch (\Exception $e) {
            $settings = collect([]);
        }
        
        // Bagikan variabel $settings ke semua view yang ada di aplikasi.
        View::share('settings', $settings);
    }
}
