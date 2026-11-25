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

use App\Models\Anggota;
use App\Observers\AnggotaObserver;

use App\Models\Surat;
use App\Observers\SuratObserver;

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
        // Terapkan locale Bahasa Indonesia untuk format tanggal (Carbon/Filament)
        // agar nama bulan tampil dalam Bahasa Indonesia (mis. "Mei", "Agu", "Okt").
        \Illuminate\Support\Carbon::setLocale('id');

        Keuangan::observe(KeuanganObserver::class);
        Dokumentasi::observe(DokumentasiObserver::class);
        Kegiatan::observe(KegiatanObserver::class);
        // Observer ini menjaga sinkronisasi cache statistik Dashboard
        // (StatsOverview) saat data Anggota/Surat berubah.
        Anggota::observe(AnggotaObserver::class);
        Surat::observe(SuratObserver::class);

        // Ambil & cache data settings HANYA saat view publik dirender.
        //
        // Sebelumnya logika ini dijalankan pada setiap request (termasuk request
        // internal Livewire & panel admin Filament yang tidak membutuhkan $settings),
        // sehingga setiap interaksi admin ikut memanggil cache/DB settings secara
        // sia-sia. Dengan View::composer, pengambilan data hanya terjadi ketika salah
        // satu view publik di bawah ini benar-benar dirender.
        //
        // Data diambil dari cache 'settings'. Jika belum ada, ambil dari database,
        // ubah menjadi collection, lalu simpan di cache selamanya. Cache di-invalidate
        // pada PengaturanWebsite::save() via Cache::forget('settings').
        View::composer(
            ['layouts.public', 'welcome', 'agenda', 'agenda-show', 'galeri', 'pages.*'],
            function ($view) {
                try {
                    $settings = Cache::rememberForever('settings', function () {
                        // collect() mengubah array hasil pluck menjadi Laravel Collection
                        return collect(Setting::pluck('value', 'key'));
                    });
                } catch (\Exception $e) {
                    $settings = collect([]);
                }

                $view->with('settings', $settings);
            }
        );
    }
}
