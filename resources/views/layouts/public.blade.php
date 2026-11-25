<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $siteName = ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti');
    @endphp
    <title>@yield('title', $siteName)</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @stack('styles')
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

        /* Sembunyikan komponen Alpine sebelum inisialisasi agar tidak berkedip */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased selection:bg-amber-500/30 selection:text-amber-400">

    <!-- Background Elements -->
    <div class="fixed inset-0 grid-bg z-[-1]"></div>
    <div class="blob-glow top-[-20%] left-[-10%] hidden md:block"></div>
    <div class="blob-glow bottom-[-20%] right-[-10%] bg-[radial-gradient(circle,rgba(99,102,241,0.1)_0%,rgba(0,0,0,0)_70%)] hidden md:block"></div>

    @php
        // Menu navigasi terstandar untuk seluruh halaman publik
        $navLinks = [
            ['label' => 'Beranda', 'href' => route('welcome'), 'active' => request()->routeIs('welcome')],
            ['label' => 'Kegiatan', 'href' => route('agenda.index'), 'active' => request()->routeIs('agenda.*')],
            ['label' => 'Galeri', 'href' => route('galeri.index'), 'active' => request()->routeIs('galeri.*')],
            ['label' => 'Lokasi', 'href' => url('/#lokasi'), 'active' => false],
        ];
    @endphp

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300 border-b border-white/5 bg-[#0c0a09]/80 backdrop-blur-md">
        <div class="container mx-auto px-4 md:px-6 h-20 flex justify-between items-center relative z-50">
            <div class="flex items-center gap-3">
                <a href="{{ route('welcome') }}" class="font-bold text-lg md:text-xl tracking-tight text-white truncate max-w-[200px] md:max-w-none hover:text-amber-400 transition-colors">
                    {{ $siteName }}
                </a>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-400">
                @foreach($navLinks as $link)
                    <a href="{{ $link['href'] }}" class="{{ $link['active'] ? 'text-amber-400' : 'hover:text-white' }} transition-colors">{{ $link['label'] }}</a>
                @endforeach
            </div>

            <div class="hidden md:flex items-center gap-4">
                <a href="/admin" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-black transition-all duration-200 bg-amber-400 rounded-full hover:bg-amber-300 hover:shadow-[0_0_20px_rgba(251,191,36,0.4)] focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-amber-400 focus-visible:ring-offset-gray-900">
                    Login Panel
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" aria-label="Buka menu navigasi" class="md:hidden text-gray-300 hover:text-white focus-visible:ring-2 focus-visible:ring-amber-400 rounded-lg p-2 z-[60]">
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
            <button id="mobile-menu-close" aria-label="Tutup menu" class="text-gray-400 hover:text-white transition-colors rounded-lg p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-2">
            @foreach($navLinks as $link)
                <a href="{{ $link['href'] }}" class="mobile-link block px-4 py-3 {{ $link['active'] ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/5' }} rounded-lg transition-colors font-medium">{{ $link['label'] }}</a>
            @endforeach

            <div class="mt-auto pt-8 border-t border-white/10">
                <a href="/admin" class="w-full inline-flex items-center justify-center px-6 py-3 text-base font-bold text-black transition-all duration-200 bg-amber-400 rounded-lg hover:bg-amber-300 shadow-lg shadow-amber-500/20">
                    Login Panel
                </a>
            </div>
        </div>
    </div>

    {{-- Konten halaman --}}
    @yield('content')

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-[#0c0a09] relative z-10 py-12">
        <div class="container mx-auto px-4 md:px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-3">
                <span class="text-gray-400 font-medium text-center md:text-left">© {{ date('Y') }} {{ $siteName }}. All rights reserved.</span>
            </div>
            <div class="flex gap-6 text-gray-400">
                <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition-colors">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-amber-400 transition-colors">Terms</a>
                <a href="{{ route('contact') }}" class="hover:text-amber-400 transition-colors">Contact</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
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
    @stack('scripts')
</body>
</html>
