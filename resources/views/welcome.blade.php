@extends('layouts.public')

@section('title', ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

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
                                        $url = $doc->file_url;
                                    @endphp
                                    <img src="{{ $url }}" 
                                         class="w-full h-full object-cover" 
                                         alt="Slide"
                                         onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia'">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex items-end p-6">
                                        <p class="text-white font-medium truncate">{{ $doc->kegiatan->nama_kegiatan ?? 'Dokumentasi' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                             <div class="swiper-slide w-[90vw] sm:w-[500px] h-[200px] sm:h-[300px] bg-gray-800 rounded-2xl overflow-hidden shadow-2xl border border-white/10">
                                <img src="{{ $fallback }}" class="w-full h-full object-cover" alt="Default Slide">
                            </div>
                        @endif
                    </div>
                    <div class="swiper-pagination mt-10"></div>
                </div>
            </div>
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
                        $thumbUrl = optional($kegiatan->dokumentasi->first())->file_url ?? 'https://placehold.co/800x600/1e293b/fbbf24?text=Kegiatan';
                        // First item is big (Featured)
                        $classes = $index === 0 ? 'md:col-span-2 md:row-span-2' : 'col-span-1 row-span-1';
                        $imageHeight = $index === 0 ? 'h-full' : 'h-48 md:h-full';
                    @endphp
                    
                    <a href="{{ route('agenda.show', $kegiatan->id) }}" class="group relative rounded-3xl overflow-hidden {{ $classes }} border border-white/10 shadow-2xl">
                        <img src="{{ $thumbUrl }}" 
                             alt="{{ $kegiatan->nama_kegiatan }}" 
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
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
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4"
                 x-data="{ lightboxOpen: false, lightboxSrc: '' }"
                 x-effect="document.body.style.overflow = lightboxOpen ? 'hidden' : ''">
                @forelse($dokumentasiTerbaru->take(12) as $doc)
                    @php $photoUrl = $doc->file_url; @endphp
                    <button type="button"
                            class="aspect-square glass-card rounded-2xl p-2 flex items-center justify-center group relative overflow-hidden hover:bg-white/5 transition-colors cursor-pointer focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0c0a09]"
                            data-aos="fade-up"
                            aria-label="Lihat foto dokumentasi ukuran penuh"
                            @click="lightboxSrc = @js($photoUrl); lightboxOpen = true">
                        <div class="w-full h-full rounded-xl overflow-hidden relative">
                             <img src="{{ $photoUrl }}" 
                                  alt="Dokumentasi" 
                                  class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                                  onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia'">
                             
                             <!-- Hover Overlay: indikasi kartu dapat diklik -->
                             <div class="absolute inset-0 bg-amber-500/70 flex items-center justify-center opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white drop-shadow-lg"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>
                             </div>
                        </div>
                    </button>
                @empty
                     <p class="col-span-full text-center text-gray-400">Belum ada dokumentasi.</p>
                @endforelse
                
                <!-- 'More' Card -->
                <a href="{{ route('galeri.index') }}" class="aspect-square glass-panel rounded-2xl flex flex-col items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 transition-all group">
                    <span class="text-3xl font-light mb-2 group-hover:scale-110 transition-transform duration-300">+</span>
                    <span class="text-xs font-semibold uppercase tracking-wider">Lihat Semua</span>
                </a>

                <!-- Modal Preview Lightbox -->
                <div x-cloak
                     x-show="lightboxOpen"
                     x-transition.opacity
                     @keydown.escape.window="lightboxOpen = false"
                     @click.self="lightboxOpen = false"
                     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm p-4 md:p-8"
                     role="dialog"
                     aria-modal="true"
                     aria-label="Pratinjau foto dokumentasi">
                    <button type="button"
                            class="absolute top-4 right-4 md:top-6 md:right-6 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-2 transition-colors focus-visible:ring-2 focus-visible:ring-amber-400"
                            aria-label="Tutup pratinjau"
                            @click="lightboxOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                    <img :src="lightboxSrc"
                         alt="Pratinjau foto dokumentasi"
                         class="max-w-full max-h-full rounded-2xl object-contain shadow-2xl"
                         @click.stop>
                </div>
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
    @if(($settings ?? collect())->get('lokasi_map'))
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
                    loading="lazy"
                    allowfullscreen
                    title="Peta lokasi Banjar Karya Dharma Sesetan"
                    src="{{ ($settings ?? collect())->get('lokasi_map') }}">
                </iframe>
            </div>
        </div>
    </section>
    @endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new Swiper('.mySwiper', {
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
    });
</script>
@endpush
