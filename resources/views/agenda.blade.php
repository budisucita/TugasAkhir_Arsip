<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agenda & Kegiatan - {{ $settings->get('judul_website', 'ST. Dharma Bhakti') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #0c0a09;
            color: #e5e7eb;
            overflow-x: hidden;
        }
        
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

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0c0a09; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }
    </style>
</head>
<body class="antialiased selection:bg-amber-500/30 selection:text-amber-400">

    <!-- Background Elements -->
    <div class="fixed inset-0 grid-bg z-[-1]"></div>
    <div class="blob-glow top-[-20%] left-[-10%] hidden md:block"></div>
    <div class="blob-glow bottom-[-20%] right-[-10%] bg-[radial-gradient(circle,rgba(99,102,241,0.1)_0%,rgba(0,0,0,0)_70%)] hidden md:block"></div>

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300 border-b border-white/5 bg-[#0c0a09]/80 backdrop-blur-md">
        <div class="container mx-auto px-6 h-20 flex justify-between items-center relative z-50">
            <div class="flex items-center gap-3">
                <a href="{{ route('welcome') }}" class="font-bold text-lg md:text-xl tracking-tight text-white truncate max-w-[200px] md:max-w-none hover:text-amber-400 transition-colors">
                    {{ $settings->get('judul_website', 'ST. Dharma Bhakti') }}
                </a>
            </div>
            
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-400">
                <a href="{{ route('welcome') }}" class="hover:text-white transition-colors">Beranda</a>
                <a href="{{ route('agenda.index') }}" class="text-amber-400 transition-colors">Kegiatan</a>
                <a href="{{ route('galeri.index') }}" class="hover:text-white transition-colors">Galeri</a>
                <a href="{{ url('/#lokasi') }}" class="hover:text-white transition-colors">Lokasi</a>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <a href="/admin" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-black transition-all duration-200 bg-amber-400 rounded-full hover:bg-amber-300 hover:shadow-[0_0_20px_rgba(251,191,36,0.4)] focus:outline-none focus:border-amber-600 focus:ring-2 focus:ring-offset-2 focus:ring-amber-400 focus:ring-offset-gray-900">
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
            <a href="{{ route('welcome') }}" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Beranda</a>
            <a href="{{ route('agenda.index') }}" class="mobile-link block px-4 py-3 text-white bg-white/10 rounded-lg transition-colors font-medium">Kegiatan</a>
            <a href="{{ route('galeri.index') }}" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Galeri</a>
            
            <div class="mt-auto pt-8 border-t border-white/10">
                <a href="/admin" class="w-full inline-flex items-center justify-center px-6 py-3 text-base font-bold text-black transition-all duration-200 bg-amber-400 rounded-lg hover:bg-amber-300 shadow-lg shadow-amber-500/20">
                    Login Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="pt-32 pb-20 px-6 container mx-auto relative z-10" x-data="{ view: 'grid' }">
        
        <!-- Header Section -->
        <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 tracking-tight">
                Agenda & <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-600">Kegiatan</span>
            </h1>
            <p class="text-gray-400 text-lg">
                Ikuti terus perkembangan terbaru dari setiap acara yang kami selenggarakan.
            </p>
        </div>

        <!-- Next Event Highlight -->
        @if($nextEvent)
        <div class="mb-20" data-aos="fade-up" data-aos-delay="100">
            <div class="glass-panel rounded-3xl p-1 md:p-2 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="bg-[#0c0a09]/80 rounded-2xl overflow-hidden relative grid md:grid-cols-2 gap-8 items-center p-6 md:p-12">
                    <!-- Image -->
                    <div class="relative aspect-video rounded-xl overflow-hidden shadow-2xl">
                        @if($nextEvent->dokumentasi->isNotEmpty())
                            <img src="{{ Storage::url($nextEvent->dokumentasi->first()->file_path) }}" alt="{{ $nextEvent->nama_kegiatan }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                        @else
                            <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                        <div class="absolute top-4 left-4 bg-amber-500 text-black font-bold px-4 py-1 rounded-full text-sm animate-pulse">
                            Acara Berikutnya
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-amber-400 font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $nextEvent->tanggal_mulai->format('d F Y') }}</span>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-white">{{ $nextEvent->nama_kegiatan }}</h2>
                        </div>
                        
                        <div class="text-gray-400 line-clamp-3">
                            {!! $nextEvent->deskripsi !!}
                        </div>
                        
                        <a href="{{ route('agenda.show', $nextEvent->id) }}" class="inline-flex min-h-[44px] px-8 py-3 bg-white text-black font-bold rounded-xl hover:bg-amber-400 transition-all duration-300 shadow-lg hover:shadow-amber-500/25 items-center gap-2">
                            Lihat Detail
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Popular Activities Section -->
        @if(isset($popularKegiatan) && $popularKegiatan->isNotEmpty())
        <div class="mb-20">
            <div class="flex items-center justify-between mb-8" data-aos="fade-up">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                    </svg>
                    Kegiatan Populer
                </h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($popularKegiatan as $pop)
                <a href="{{ route('agenda.show', $pop->id) }}" class="glass-card rounded-xl overflow-hidden group block" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 50 }}">
                    <div class="aspect-square relative overflow-hidden">
                        @if($pop->dokumentasi->isNotEmpty())
                            <img src="{{ Storage::url($pop->dokumentasi->first()->file_path) }}" alt="{{ $pop->nama_kegiatan }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @else
                            <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-60 group-hover:opacity-80 transition-opacity"></div>
                        <div class="absolute bottom-3 left-3 right-3">
                            <h4 class="text-white font-bold text-sm line-clamp-2 group-hover:text-amber-400 transition-colors">{{ $pop->nama_kegiatan }}</h4>
                            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ $pop->views }} views
                            </p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Filter & Search Section -->
        <div class="glass-panel p-6 rounded-2xl mb-8 sticky top-24 z-40 shadow-xl" data-aos="fade-down">
            <form action="{{ route('agenda.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <!-- Search -->
                <div class="md:col-span-4 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kegiatan..." 
                           class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-5 py-3 pl-12 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all text-white placeholder-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filters -->
                <div class="md:col-span-2">
                    <select name="bulan" class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all text-white appearance-none cursor-pointer">
                        <option value="">Semua Bulan</option>
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <select name="tahun" class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all text-white appearance-none cursor-pointer">
                        <option value="">Semua Tahun</option>
                        @foreach(range(date('Y'), 2020) as $y)
                            <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <select name="status" class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all text-white appearance-none cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="akan_datang" {{ request('status') == 'akan_datang' ? 'selected' : '' }}>Akan Datang</option>
                        <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-2">
                    <button type="submit" class="w-full h-full bg-amber-500 hover:bg-amber-400 text-black font-bold rounded-xl transition-colors flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span class="ml-2 md:hidden">Cari</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- View Toggles -->
        <div class="flex justify-end mb-6">
            <div class="bg-white/5 p-1 rounded-xl flex gap-1">
                <button @click="view = 'grid'" :class="view === 'grid' ? 'bg-amber-500 text-black shadow-lg' : 'text-gray-400 hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Grid
                </button>
                <button @click="view = 'calendar'" :class="view === 'calendar' ? 'bg-amber-500 text-black shadow-lg' : 'text-gray-400 hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Kalender
                </button>
            </div>
        </div>

        @if($kegiatan->count() > 0)
            <!-- Grid View -->
            <div x-show="view === 'grid'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                @foreach($kegiatan as $item)
                <div class="glass-card rounded-2xl overflow-hidden group flex flex-col h-full">
                    <!-- Image -->
                    <div class="relative h-56 overflow-hidden">
                        @if($item->dokumentasi->isNotEmpty())
                            <img src="{{ Storage::disk('public')->url($item->dokumentasi->first()->file_path) }}" alt="{{ $item->nama_kegiatan }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        @else
                            <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                        
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            @if($item->status == 'Selesai')
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30 backdrop-blur-md">
                                    Selesai
                                </span>
                            @elseif($item->status == 'Berlangsung')
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 backdrop-blur-md animate-pulse">
                                    Berlangsung
                                </span>
                            @elseif($item->status == 'Dibatalkan')
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30 backdrop-blur-md">
                                    Dibatalkan
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 backdrop-blur-md">
                                    Akan Datang
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $item->tanggal_mulai->format('d M Y') }}</span>
                            @if(filled($item->lokasi))
                            <span class="mx-1">•</span>
                            <span class="truncate max-w-[120px]">{{ $item->lokasi }}</span>
                            @endif
                        </div>

                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-amber-400 transition-colors line-clamp-2">
                            {{ $item->nama_kegiatan }}
                        </h3>

                        <div class="text-gray-400 text-sm line-clamp-3 mb-6 flex-1">
                            {!! strip_tags($item->deskripsi) !!}
                        </div>

                        <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
                            <a href="{{ route('agenda.show', $item->id) }}" class="inline-flex min-h-[44px] px-4 py-2 rounded-full text-sm font-semibold text-black bg-white hover:bg-amber-400 transition-colors items-center gap-2">
                                Lihat Detail
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                            
                            <!-- Share Button -->
                            <button onclick="shareItem('{{ $item->nama_kegiatan }}', '{{ route('agenda.show', $item->id) }}')" class="p-2 hover:bg-white/10 rounded-full transition-colors text-gray-400 hover:text-white" title="Bagikan">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Calendar/List View -->
            <div x-show="view === 'calendar'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-4 mb-16" style="display: none;">
                @foreach($kegiatan as $item)
                <div class="glass-panel p-4 rounded-xl flex flex-col md:flex-row gap-6 items-center group hover:bg-white/5 transition-colors">
                    <div class="flex-shrink-0 text-center md:w-24 bg-white/5 rounded-lg p-3 border border-white/10">
                        <div class="text-sm text-amber-500 font-bold uppercase">{{ $item->tanggal_mulai->format('M') }}</div>
                        <div class="text-3xl font-bold text-white">{{ $item->tanggal_mulai->format('d') }}</div>
                        <div class="text-xs text-gray-400">{{ $item->tanggal_mulai->format('D') }}</div>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <h3 class="text-xl font-bold text-white group-hover:text-amber-400 transition-colors mb-2">
                            <a href="{{ route('agenda.show', $item->id) }}">{{ $item->nama_kegiatan }}</a>
                        </h3>
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-sm text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $item->tanggal_mulai->format('H:i') }}
                            </span>
                            @if(filled($item->lokasi))
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $item->lokasi }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                         @if($item->status == 'Selesai')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30">
                                Selesai
                            </span>
                        @elseif($item->status == 'Berlangsung')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 animate-pulse">
                                Berlangsung
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                Akan Datang
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-12" data-aos="fade-up">
                {{ $kegiatan->appends(request()->query())->links('vendor.pagination.tailwind') }}
            </div>
        @else
            <div class="text-center py-20 glass-panel rounded-3xl" data-aos="fade-up">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-800 mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Tidak ada kegiatan ditemukan</h3>
                <p class="text-gray-400">Coba ubah filter pencarian Anda atau cek kembali nanti.</p>
                <a href="{{ route('agenda.index') }}" class="inline-block mt-6 text-amber-500 hover:text-amber-400 font-medium">Reset Filter</a>
            </div>
        @endif

    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-[#0c0a09] relative z-10">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-gray-500 text-sm">
                    © {{ date('Y') }} {{ $settings->get('judul_website', 'ST. Dharma Bhakti') }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        AOS.init({
            once: true,
            duration: 800,
            offset: 100,
        });

        function shareItem(title, url) {
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(console.error);
            } else {
                // Fallback for desktop or unsupported browsers
                const shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
                window.open(shareUrl, '_blank');
            }
        }

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
