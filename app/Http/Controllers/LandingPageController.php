<?php

namespace App\Http\Controllers;

use App\Models\Anggota; 
use App\Models\Dokumentasi;
use App\Models\Kegiatan;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LandingPageController extends Controller
{
    public function index()
    {
        $settings = $this->getSettings();
        
        $kegiatanTerbaru = Cache::remember('kegiatan_terbaru_landing', 3600, function () {
            // Tanpa relasi 'user': data akun penginput bersifat internal dan
            // tidak boleh terekspos pada halaman publik.
            return Kegiatan::with(['dokumentasi' => function($q) {
                $q->where('tipe', 'foto')->latest();
            }])
                ->where('status', 'Selesai')
                ->orderBy('tanggal_mulai', 'desc')
                ->take(6)
                ->get();
        });

        $dokumentasiTerbaru = Cache::remember('dokumentasi_terbaru_landing', 3600, function () {
            return Dokumentasi::with('kegiatan')
                ->where('tipe', 'foto')
                ->latest()
                ->take(6)
                ->get();
        });

        // --- BAGIAN PENTING YANG MEMPERBAIKI ERROR ---
        // Mengambil data statistik dan menyimpannya di variabel $statistik
        $statistik = Cache::remember('statistik_landing', 3600, function () {
            return [
                'anggota_aktif' => Anggota::where('status', 'aktif')->count(),
                'kegiatan_selesai' => Kegiatan::where('status', 'Selesai')->count(),
                'total_dokumentasi' => Dokumentasi::count(),
            ];
        });
        // ---------------------------------------------

        return view('welcome', [
            'settings' => $settings,
            'kegiatanTerbaru' => $kegiatanTerbaru,
            'dokumentasiTerbaru' => $dokumentasiTerbaru,
            'statistik' => $statistik, 
        ]);
    }

    public function galeri(Request $request)
    {
        $settings = $this->getSettings();

        $query = Dokumentasi::with('kegiatan')->where('tipe', 'foto');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('kegiatan', function($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $dokumentasi = $query->latest()->paginate(12);

        return view('galeri', [
            'settings' => $settings,
            'dokumentasi' => $dokumentasi,
        ]);
    }

    public function agenda(Request $request)
    {
        $settings = $this->getSettings();

        $query = Kegiatan::with(['dokumentasi' => function($q) {
            $q->where('tipe', 'foto')->latest();
        }])
            // Kegiatan berstatus Dibatalkan bersifat internal dan tidak boleh
            // tampil di daftar agenda publik mana pun.
            ->where('status', '!=', 'Dibatalkan');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_mulai', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_mulai', $request->tahun);
        }
        
        if ($request->filled('status')) {
             if($request->status == 'akan_datang') {
                 $query->where(function($q) {
                    $q->where('status', 'Direncanakan')
                      ->orWhere('status', 'Berlangsung');
                 })->whereDate('tanggal_mulai', '>=', now());
             } elseif($request->status == 'selesai') {
                 $query->where('status', 'Selesai');
             }
        }

        $kegiatan = $query->orderBy('tanggal_mulai', 'desc')->paginate(9);

        $nextEvent = Kegiatan::with(['dokumentasi' => function($q) {
                $q->where('tipe', 'foto')->latest();
            }])
            ->where('status', '!=', 'Selesai')
            ->where('status', '!=', 'Dibatalkan')
            ->whereDate('tanggal_mulai', '>=', now())
            ->orderBy('tanggal_mulai', 'asc')
            ->first();

        $popularKegiatan = Kegiatan::with(['dokumentasi' => function($q) {
                $q->where('tipe', 'foto')->latest();
            }])
            ->where('status', '!=', 'Dibatalkan')
            ->orderBy('tanggal_mulai', 'desc')
            ->take(5)
            ->get();

        return view('agenda', [
            'settings' => $settings,
            'kegiatan' => $kegiatan,
            'nextEvent' => $nextEvent,
            'popularKegiatan' => $popularKegiatan,
        ]);
    }

    public function agendaDetail($id)
    {
        $settings = $this->getSettings();

        // Detail kegiatan berstatus Dibatalkan tidak boleh diakses publik: 404.
        $kegiatan = Kegiatan::with('dokumentasi')
            ->where('status', '!=', 'Dibatalkan')
            ->findOrFail($id);

        $relatedKegiatan = Kegiatan::with(['dokumentasi' => function($q) {
                $q->where('tipe', 'foto')->latest();
            }])
            ->where('id', '!=', $id)
            ->where('status', $kegiatan->status)
            ->take(3)
            ->get();

        return view('agenda-show', [
            'settings' => $settings,
            'kegiatan' => $kegiatan,
            'relatedKegiatan' => $relatedKegiatan,
        ]);
    }

    public function privacy()
    {
        return view('pages.privacy', ['settings' => $this->getSettings()]);
    }

    public function terms()
    {
        return view('pages.terms', ['settings' => $this->getSettings()]);
    }

    public function contact()
    {
        return view('pages.contact', ['settings' => $this->getSettings()]);
    }

    /**
     * Sumber tunggal (single source of truth) data settings portal publik.
     * Hasilnya di-cache permanen pada key 'settings' sehingga pemanggilan
     * dari method mana pun tidak menyentuh database.
     */
    private function getSettings()
    {
        return Cache::rememberForever('settings', function () {
            return Setting::all()->pluck('value', 'key');
        });
    }
}
