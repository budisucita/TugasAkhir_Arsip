<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->get('judul_website', 'ST. Dharma Bhakti') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #0c0a09;
            color: #e5e7eb;
            overflow-x: hidden;
        }
        
        /* Glassmorphism Utilities */
        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.5);
        }

        /* Decorative Elements */
        .grid-bg {
            background-size: 50px 50px;
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        }
        
        .blob-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.15) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 8s ease-in-out infinite 2s; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0c0a09; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }
        
        /* Swiper Custom Pagination */
        .swiper-pagination-bullet { background: #666; opacity: 0.5; }
        .swiper-pagination-bullet-active { background: #f59e0b; opacity: 1; }
    </style>
</head>
<body class="antialiased selection:bg-amber-500/30 selection:text-amber-400">

    <!-- Background Elements -->
    <div class="fixed inset-0 grid-bg z-[-1]"></div>
    <div class="blob-glow top-[-20%] left-[-10%] hidden md:block"></div>
    <div class="blob-glow bottom-[-20%] right-[-10%] bg-[radial-gradient(circle,rgba(99,102,241,0.1)_0%,rgba(0,0,0,0)_70%)] hidden md:block"></div>

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300 border-b border-white/5 bg-[#0c0a09]/80 backdrop-blur-md">
        <div class="container mx-auto px-4 md:px-6 h-20 flex justify-between items-center relative z-50">
            <div class="flex items-center gap-3">
                {{-- <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center text-black font-bold text-xl shadow-lg shadow-amber-500/20">
                    DB
                </div> --}}
                <span class="font-bold text-lg md:text-xl tracking-tight text-white truncate max-w-[200px] md:max-w-none">{{ $settings->get('judul_website', 'ST. Dharma Bhakti') }}</span>
            </div>
            
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-400">
                <a href="#" class="hover:text-white transition-colors">Beranda</a>
                <a href="#kegiatan" class="hover:text-white transition-colors">Kegiatan</a>
                <a href="#dokumentasi" class="hover:text-white transition-colors">Galeri</a>
                <a href="{{ url('/#lokasi') }}" class="hover:text-white transition-colors">Lokasi</a>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <a href="/admin" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-black transition-all duration-200 bg-amber-400 rounded-full hover:bg-amber-300 hover:shadow-[0_0_20px_rgba(251,191,36,0.4)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400 focus:ring-offset-gray-900">
                    Login Panel
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-300 hover:text-white focus:outline-none p-2 z-[60]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </nav>

    <!-- Offcanvas Menu Overlay (Backdrop) -->
    <div id="mobile-menu-backdrop" class="fixed inset-0 bg-black/60 z-[55] hidden transition-opacity duration-300 opacity-0 backdrop-blur-sm"></div>

    <!-- Offcanvas Menu Sidebar -->
    <div id="mobile-menu-sidebar" class="fixed inset-y-0 right-0 z-[60] w-72 bg-[#121212] shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out border-l border-white/10 flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-white/10 bg-[#18181b]">
            <h5 class="text-lg font-bold text-white tracking-wide">Menu</h5>
            <button id="mobile-menu-close" class="text-gray-400 hover:text-white transition-colors p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-2">
            <a href="#" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Beranda</a>
            <a href="#kegiatan" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Kegiatan</a>
            <a href="#dokumentasi" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Galeri</a>
            
            <div class="mt-auto pt-8 border-t border-white/10">
                <a href="/admin" class="w-full inline-flex items-center justify-center px-6 py-3 text-base font-bold text-black transition-all duration-200 bg-amber-400 rounded-lg hover:bg-amber-300 shadow-lg shadow-amber-500/20">
                    Login Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative pt-24 md:pt-40 pb-12 overflow-hidden">
        <div class="container mx-auto px-4 md:px-6 relative z-10 text-center">
            
            <!-- Floating Elements (Decorative) -->
            <div class="absolute top-0 left-10 md:left-40 hidden md:block animate-float glass-panel p-3 rounded-2xl rotate-[-6deg]">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
            </div>
            <div class="absolute top-10 right-10 md:right-40 hidden md:block animate-float-delayed glass-panel p-3 rounded-2xl rotate-[6deg]">
                <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
            </div>

            <!-- Main Title -->
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-amber-400 text-xs font-semibold uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                Official Website
            </div>
            
            <h1 class="text-4xl md:text-7xl font-extrabold text-white tracking-tight leading-[1.1] mb-6 max-w-4xl mx-auto">
                Merekam Jejak, <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500">Merajut Kebersamaan</span>
            </h1>
            
            <p class="text-lg text-gray-400 max-w-2xl mx-auto mb-12 leading-relaxed">
                Portal digital resmi untuk setiap arsip kegiatan, dokumentasi, dan momen berharga yang kami bangun bersama.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-20">
                <a href="#kegiatan" class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-black font-bold hover:bg-gray-200 transition-all flex items-center justify-center gap-2">
                    Jelajahi Kegiatan
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m12 16 4-4-4-4"/><path d="M8 12h8"/></svg>
                </a>
                <a href="#dokumentasi" class="w-full sm:w-auto px-8 py-4 rounded-full glass-panel text-white font-semibold hover:bg-white/10 transition-all flex items-center justify-center gap-2">
                    Lihat Galeri
                </a>
            </div>

            <!-- Swiper Carousel (Coverflow) -->
            <div class="relative w-full max-w-5xl mx-auto perspective-1000">
                <div class="swiper mySwiper w-full py-10">
                    <div class="swiper-wrapper">
                        @php
                            $heroImages = collect();
                            
                            // Add latest documentation images
                            if (isset($dokumentasiTerbaru)) {
                                $heroImages = $heroImages->merge($dokumentasiTerbaru->take(5));
                            }
                            
                            // Duplikasi slide jika kurang dari 6 agar loop berjalan lancar
                            if ($heroImages->count() > 0 && $heroImages->count() < 6) {
                                $original = $heroImages;
                                while ($heroImages->count() < 6) {
                                    $heroImages = $heroImages->merge($original);
                                }
                            }
                            
                            $fallback = 'https://placehold.co/1600x900/1e293b/fbbf24?text=ST.+Dharma+Bhakti';
                        @endphp
                        
                        @if($heroImages->isNotEmpty())
                            @foreach($heroImages as $doc)
                                <div class="swiper-slide w-[90vw] sm:w-[500px] h-[200px] sm:h-[300px] bg-gray-800 rounded-2xl overflow-hidden shadow-2xl border border-white/10">
                                    @php
                                        // Handle both Setting (string) and Dokumentasi (model with accessor) paths
                                        $path = $doc->is_setting ?? false ? $doc->file_path : $doc->file_path;
                                        $url = Illuminate\Support\Facades\Storage::url($path);
                                    @endphp
                                    <img src="{{ $url }}" 
                                         class="w-full h-auto object-cover" 
                                         alt="Slide"
                                         onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia'">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex items-end p-6">
                                        <p class="text-white font-medium truncate">{{ $doc->kegiatan->nama_kegiatan ?? 'Dokumentasi' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                             <div class="swiper-slide w-[90vw] sm:w-[500px] h-[200px] sm:h-[300px] bg-gray-800 rounded-2xl overflow-hidden shadow-2xl border border-white/10">
                                <img src="{{ $fallback }}" class="w-full h-auto object-cover" alt="Default Slide">
                            </div>
                        @endif
                    </div>
                    <div class="swiper-pagination mt-10"></div>
                </div>
            </div>

            <!-- Trusted By / Stats Strip -->
            {{-- <div class="mt-20 border-t border-white/5 pt-10">
                <p class="text-sm text-gray-500 font-medium mb-6 uppercase tracking-widest">Dipercaya & Didukung Oleh</p>
                <div class="flex flex-wrap justify-center gap-8 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                    <!-- Placeholder Logos with Text -->
                    <div class="flex items-center gap-2 text-xl font-bold text-white"><span class="text-amber-500 text-2xl">❖</span> Mitra</div>
                    <div class="flex items-center gap-2 text-xl font-bold text-white"><span class="text-blue-500 text-2xl">◈</span> Sponsor</div>
                    <div class="flex items-center gap-2 text-xl font-bold text-white"><span class="text-green-500 text-2xl">◉</span> Donatur</div>
                    <div class="flex items-center gap-2 text-xl font-bold text-white"><span class="text-purple-500 text-2xl">◎</span> Komunitas</div>
                </div>
            </div> --}}
        </div>
    </section>

    <!-- Bento Grid Section: Kegiatan -->
    <section id="kegiatan" class="py-24 relative">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                <div>
                    <h2 class="text-3xl md:text-5xl font-bold text-white mb-4">Agenda & <span class="text-amber-400">Kegiatan</span></h2>
                    <p class="text-gray-400 max-w-md">Ikuti terus perkembangan terbaru dari setiap acara yang kami selenggarakan.</p>
                </div>
                <a href="{{ route('agenda.index') }}" class="inline-flex items-center gap-2 text-amber-400 font-semibold hover:text-amber-300 transition-colors">
                    Lihat Semua
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>

            <!-- Bento Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 md:grid-rows-2 gap-6 h-auto md:h-[600px]">
                @forelse($kegiatanTerbaru->take(4) as $index => $kegiatan)
                    @php
                        $thumb = optional($kegiatan->dokumentasi->first())->file_path;
                        $thumbUrl = $thumb ? Illuminate\Support\Facades\Storage::url($thumb) : 'https://placehold.co/800x600/1e293b/fbbf24?text=Kegiatan';
                        // First item is big (Featured)
                        $classes = $index === 0 ? 'md:col-span-2 md:row-span-2' : 'col-span-1 row-span-1';
                        $imageHeight = $index === 0 ? 'h-full' : 'h-48 md:h-full';
                    @endphp
                    
                    <a href="{{ route('agenda.show', $kegiatan->id) }}" class="group relative rounded-3xl overflow-hidden {{ $classes }} border border-white/10 shadow-2xl">
                        <img src="{{ $thumbUrl }}" 
                             alt="{{ $kegiatan->nama_kegiatan }}" 
                             class="w-full h-auto object-cover transition-transform duration-700 group-hover:scale-105"
                             onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Kegiatan'">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent p-6 md:p-8 flex flex-col justify-end">
                            <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                <span class="inline-block px-3 py-1 rounded-full bg-amber-500 text-black text-xs font-bold mb-3">
                                    {{ $kegiatan->tanggal_mulai->format('d M Y') }}
                                </span>
                                <h3 class="{{ $index === 0 ? 'text-3xl' : 'text-xl' }} font-bold text-white mb-2 leading-tight">
                                    {{ $kegiatan->nama_kegiatan }}
                                </h3>
                                @if($index === 0)
                                <p class="text-gray-300 line-clamp-2 mb-4">
                                    {{ Str::limit(strip_tags($kegiatan->deskripsi), 150) }}
                                </p>
                                @endif
                                <div class="flex items-center gap-2 text-amber-400 text-sm font-semibold opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
                                    Baca Selengkapnya <span class="text-lg">→</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full glass-card rounded-3xl p-12 text-center">
                        <p class="text-gray-400 text-lg">Belum ada kegiatan terbaru.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Dokumentasi Section (Integration Grid Style) -->
    <section id="dokumentasi" class="py-24 bg-[#0c0a09] relative border-t border-white/5">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl md:text-5xl font-bold text-white mb-6">Galeri <span class="text-amber-400">Arsip</span></h2>
                <p class="text-gray-400 text-lg">Koleksi visual yang merekam setiap momen berharga dan sejarah perjalanan kami.</p>
            </div>

            <!-- Grid Layout mimicking 'Integrations' -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @forelse($dokumentasiTerbaru->take(12) as $doc)
                    <div class="aspect-square glass-card rounded-2xl p-2 flex items-center justify-center group relative overflow-hidden hover:bg-white/5 transition-colors cursor-pointer" data-aos="fade-up">
                        <div class="w-full h-full rounded-xl overflow-hidden relative">
                             <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($doc->file_path) }}" 
                                  alt="Dokumentasi" 
                                  class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                                  onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia'">
                             
                             <!-- Hover Overlay -->
                             {{-- <div class="absolute inset-0 bg-amber-500/80 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                             </div> --}}
                        </div>
                    </div>
                @empty
                     <p class="col-span-full text-center text-gray-500">Belum ada dokumentasi.</p>
                @endforelse
                
                <!-- 'More' Card -->
                <a href="{{ route('galeri.index') }}" class="aspect-square glass-panel rounded-2xl flex flex-col items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 transition-all group">
                    <span class="text-3xl font-light mb-2 group-hover:scale-110 transition-transform duration-300">+</span>
                    <span class="text-xs font-semibold uppercase tracking-wider">Lihat Semua</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Stats / Testimonial (Bottom) -->
    <section class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-amber-500/5 -skew-y-3 transform origin-left scale-110"></div>
        <div class="container mx-auto px-4 md:px-6 relative z-10">
            <div class="glass-card rounded-3xl p-6 md:p-16 text-center border border-amber-500/20 shadow-[0_0_50px_rgba(245,158,11,0.1)]">
                <div class="text-amber-400 text-6xl font-serif mb-6">"</div>
                <h3 class="text-2xl md:text-4xl font-bold text-white mb-8 leading-relaxed max-w-4xl mx-auto">
                    Sistem ini membantu transparansi informasi kepada seluruh anggota dan masyarakat yang ikut terlibat dalam kegiatan kami.
                </h3>
                {{-- <div class="flex items-center justify-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-amber-400 to-orange-500"></div>
                    <div class="text-left">
                        <div class="text-white font-bold">Ketua Organisasi</div>
                        <div class="text-amber-400 text-sm">ST. Dharma Bhakti</div>
                    </div>
                </div> --}}

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16 border-t border-white/10 pt-12">
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">{{ $statistik['anggota_aktif'] ?? '0' }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-widest">Anggota Aktif</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">{{ $statistik['kegiatan_selesai'] ?? '0' }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-widest">Kegiatan Selesai</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2">{{ $statistik['total_dokumentasi'] ?? '0' }}</div>
                        <div class="text-gray-400 text-sm uppercase tracking-widest">Arsip Foto</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Location Map (If enabled) -->
    @if($settings->get('lokasi_map'))
    <section id="lokasi" class="py-10">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl md:text-5xl font-bold text-white mb-6">Lokasi <span class="text-amber-400">Banjar</span></h2>
                <p class="text-gray-400 text-lg">"Kunjungi Kami" Temukan lokasi strategis Banjar Karya Dharma Sesetan untuk mengikuti berbagai kegiatan komunitas dan koordinasi warga.</p>
            </div>
             <div class="glass-card rounded-3xl overflow-hidden h-[400px] border border-white/10">
                <iframe
                    width="100%"
                    height="100%"
                    {{-- style="border:0; filter: grayscale(1) invert(1) contrast(1.2) brightness(0.8);" --}}
                    loading="lazy"
                    allowfullscreen
                    src="{{ $settings->get('lokasi_map') }}">
                </iframe>
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-[#0c0a09] py-12">
        <div class="container mx-auto px-4 md:px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-3">
                {{-- <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-black font-bold text-sm">DB</div> --}}
                <span class="text-gray-400 font-medium">© {{ date('Y') }} {{ $settings->get('judul_website') }}. All rights reserved.</span>
            </div>
            <div class="flex gap-6 text-gray-500">
                <a href="#" class="hover:text-amber-400 transition-colors">Privacy</a>
                <a href="#" class="hover:text-amber-400 transition-colors">Terms</a>
                <a href="#" class="hover:text-amber-400 transition-colors">Contact</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100,
            easing: 'ease-out-cubic'
        });

        const swiper = new Swiper('.mySwiper', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            speed: 1000,
            coverflowEffect: {
                rotate: 0,
                stretch: 0,
                depth: 100,
                modifier: 2.5,
                slideShadows: true,
            },
            autoplay: {
                delay: 2500,
                disableOnInteraction: false,
                pauseOnMouseEnter: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            loop: true,
            loopedSlides: 6,
            observer: true,
            observeParents: true,
        });

        // Mobile Menu Logic (Offcanvas)
        const menuBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('mobile-menu-close');
        const backdrop = document.getElementById('mobile-menu-backdrop');
        const sidebar = document.getElementById('mobile-menu-sidebar');
        const mobileLinks = document.querySelectorAll('.mobile-link');

        function openMenu() {
            backdrop.classList.remove('hidden');
            // Trigger reflow to enable transition
            void backdrop.offsetWidth;
            backdrop.classList.remove('opacity-0');
            sidebar.classList.remove('translate-x-full');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            backdrop.classList.add('opacity-0');
            sidebar.classList.add('translate-x-full');
            
            setTimeout(() => {
                backdrop.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        if (menuBtn) menuBtn.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (backdrop) backdrop.addEventListener('click', closeMenu);

        mobileLinks.forEach(link => {
            link.addEventListener('click', closeMenu);
        });
    </script>
</body>
</html>
