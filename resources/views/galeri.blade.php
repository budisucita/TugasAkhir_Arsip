<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galeri Arsip - {{ ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti') }}</title>
    @vite('resources/css/app.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
        
        .glass-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(245, 158, 11, 0.5);
            outline: none;
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
                <a href="{{ url('/') }}" class="font-bold text-lg md:text-xl tracking-tight text-white truncate max-w-[200px] md:max-w-none hover:text-amber-400 transition-colors">
                    {{ $settings?->get('judul_website', 'ST. Dharma Bhakti') ?? 'ST. Dharma Bhakti' }}
                </a>
            </div>
            
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-400">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Beranda</a>
                <a href="{{ url('/#kegiatan') }}" class="hover:text-white transition-colors">Kegiatan</a>
                <a href="{{ route('galeri.index') }}" class="text-white">Galeri</a>
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
            <a href="{{ url('/') }}" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Beranda</a>
            <a href="{{ url('/#kegiatan') }}" class="mobile-link block px-4 py-3 text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors font-medium">Kegiatan</a>
            <a href="{{ route('galeri.index') }}" class="mobile-link block px-4 py-3 text-white bg-white/5 rounded-lg transition-colors font-medium">Galeri</a>
            
            <div class="mt-auto pt-8 border-t border-white/10">
                <a href="/admin" class="w-full inline-flex items-center justify-center px-6 py-3 text-base font-bold text-black transition-all duration-200 bg-amber-400 rounded-lg hover:bg-amber-300 shadow-lg shadow-amber-500/20">
                    Login Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Section with Search -->
    <section class="relative pt-32 pb-12 overflow-hidden">
        <div class="container mx-auto px-6 relative z-10 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight mb-4">
                Galeri <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500">Dokumentasi</span>
            </h1>
            <p class="text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Telusuri koleksi momen berharga yang telah kami abadikan.
            </p>

            <!-- Search Form -->
            <form action="{{ route('galeri.index') }}" method="GET" class="max-w-2xl mx-auto relative">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400 group-focus-within:text-amber-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                        class="glass-input w-full rounded-full py-4 pl-12 pr-4 text-white placeholder-gray-400 transition-all duration-300 shadow-lg" 
                        placeholder="Cari foto kegiatan...">
                    @if(request('search'))
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <a href="{{ route('galeri.index') }}" class="p-1 rounded-full text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <!-- Gallery Grid (Masonry-like) -->
    <section class="pb-24 min-h-[50vh]">
        <div class="container mx-auto px-6">
            @if(($dokumentasi?->count() ?? 0) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($dokumentasi as $doc)
                        <div class="relative group rounded-2xl overflow-hidden glass-card hover:shadow-2xl hover:shadow-amber-500/10 transition-all duration-300" data-aos="fade-up">
                            <div class="w-full aspect-video relative overflow-hidden rounded-2xl">
                                @php
                                    $filePath = $doc->file_path ?? null;
                                    $imageUrl = $filePath ? Illuminate\Support\Facades\Storage::url($filePath) : 'https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia';
                                    $downloadUrl = $filePath ? Illuminate\Support\Facades\Storage::disk('public')->url($filePath) : '#';
                                @endphp
                                <img src="{{ $imageUrl }}" 
                                     alt="{{ $doc->kegiatan?->nama_kegiatan ?? 'Dokumentasi' }}" 
                                     class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
                                     onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
                                    <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                        <p class="text-white font-medium text-sm line-clamp-2 mb-1">{{ $doc->kegiatan?->nama_kegiatan ?? 'Dokumentasi' }}</p>
                                        <p class="text-gray-400 text-xs">{{ $doc->kegiatan?->tanggal_selesai?->format('d M Y') ?? '-' }}</p>
                                    </div>
                                    <a href="{{ $downloadUrl }}" 
                                       download 
                                       target="_blank"
                                       class="absolute bottom-6 right-6 px-4 py-2 min-h-[44px] rounded-xl bg-white text-black hover:bg-amber-400 transition-colors shadow-lg transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-75">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7 10 12 15 17 10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-16 flex justify-center">
                    {{ $dokumentasi?->appends(['search' => request('search')])->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-20 glass-panel rounded-3xl mx-auto max-w-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-xl font-bold text-white mb-2">Tidak ada foto ditemukan</h3>
                    <p class="text-gray-400">Coba kata kunci lain atau kembali lagi nanti.</p>
                    @if(request('search'))
                        <a href="{{ route('galeri.index') }}" class="inline-block mt-6 text-amber-400 hover:text-amber-300 font-medium">Reset Pencarian</a>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-[#0c0a09] py-12">
        <div class="container mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-3">
                <span class="text-gray-400 font-medium">© {{ date('Y') }} {{ $settings?->get('judul_website') ?? 'ST. Dharma Bhakti' }}. All rights reserved.</span>
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
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
            easing: 'ease-out-cubic'
        });

        // Mobile Menu Logic (Offcanvas)
        const menuBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('mobile-menu-close');
        const backdrop = document.getElementById('mobile-menu-backdrop');
        const sidebar = document.getElementById('mobile-menu-sidebar');
        const mobileLinks = document.querySelectorAll('.mobile-link');

        function openMenu() {
            backdrop.classList.remove('hidden');
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
