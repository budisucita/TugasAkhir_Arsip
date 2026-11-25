@extends('layouts.public')

@section('title', 'Galeri Arsip - ' . ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

    <!-- Hero Section with Search -->
    <section class="relative pt-32 pb-12 overflow-hidden">
        <div class="container mx-auto px-4 md:px-6 relative z-10 text-center">
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
                        aria-label="Cari foto kegiatan"
                        class="glass-input w-full rounded-full py-4 pl-12 pr-4 text-white placeholder-gray-400 transition-all duration-300 shadow-lg" 
                        placeholder="Cari foto kegiatan...">
                    @if(request('search'))
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <a href="{{ route('galeri.index') }}" aria-label="Hapus pencarian" class="p-1 rounded-full text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
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
        <div class="container mx-auto px-4 md:px-6">
            @if(($dokumentasi?->count() ?? 0) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($dokumentasi as $doc)
                        <div class="relative group rounded-2xl overflow-hidden glass-card hover:shadow-2xl hover:shadow-amber-500/10 transition-all duration-300" data-aos="fade-up">
                            <div class="w-full aspect-video relative overflow-hidden rounded-2xl">
                                @php
                                    $imageUrl = $doc->file_url ?? 'https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia';
                                    $downloadUrl = $doc->file_url ?? '#';
                                @endphp
                                <img src="{{ $imageUrl }}" 
                                     alt="{{ $doc->kegiatan?->nama_kegiatan ?? 'Dokumentasi' }}" 
                                     class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
                                     onerror="this.onerror=null; this.src='https://placehold.co/800x600/1e293b/fbbf24?text=Gambar+Tidak+Tersedia'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
                                    <div class="transform translate-y-0 md:translate-y-4 md:group-hover:translate-y-0 transition-transform duration-300">
                                        <p class="text-white font-medium text-sm line-clamp-2 mb-1">{{ $doc->kegiatan?->nama_kegiatan ?? 'Dokumentasi' }}</p>
                                        <p class="text-gray-400 text-xs">{{ $doc->kegiatan?->tanggal_selesai?->format('d M Y') ?? '-' }}</p>
                                    </div>
                                    <a href="{{ $downloadUrl }}" 
                                       download 
                                       target="_blank"
                                       aria-label="Unduh foto dokumentasi ini"
                                       class="absolute bottom-6 right-6 px-4 py-2 min-h-[44px] rounded-xl bg-white text-black hover:bg-amber-400 transition-colors shadow-lg transform translate-y-0 md:translate-y-4 md:group-hover:translate-y-0 transition-transform duration-300 delay-75">
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

@endsection
