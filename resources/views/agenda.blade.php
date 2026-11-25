@extends('layouts.public')

@section('title', 'Agenda & Kegiatan - ' . ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

    <!-- Main Content -->
    <main class="pt-32 pb-20 px-4 md:px-6 container mx-auto relative z-10" x-data="{ view: 'grid' }">
        
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Kegiatan Terbaru
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
                           class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-5 py-3 pl-12 focus-visible:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/50 transition-all text-white placeholder-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filters -->
                <div class="md:col-span-2">
                    <select name="bulan" class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-4 py-3 focus-visible:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/50 transition-all text-white appearance-none cursor-pointer">
                        <option value="" class="bg-[#121212] text-white">Semua Bulan</option>
                        @php
                            $bulanIndonesia = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ];
                        @endphp
                        @foreach($bulanIndonesia as $m => $namaBulan)
                            <option value="{{ $m }}" class="bg-[#121212] text-white" {{ request('bulan') == $m ? 'selected' : '' }}>
                                {{ $namaBulan }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <select name="tahun" class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-4 py-3 focus-visible:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/50 transition-all text-white appearance-none cursor-pointer">
                        <option value="" class="bg-[#121212] text-white">Semua Tahun</option>
                        @foreach(range(date('Y'), 2020) as $y)
                            <option value="{{ $y }}" class="bg-[#121212] text-white" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <select name="status" class="w-full bg-[#0c0a09]/50 border border-white/10 rounded-xl px-4 py-3 focus-visible:border-amber-500/50 focus-visible:ring-2 focus-visible:ring-amber-500/50 transition-all text-white appearance-none cursor-pointer">
                        <option value="" class="bg-[#121212] text-white">Semua Status</option>
                        <option value="akan_datang" class="bg-[#121212] text-white" {{ request('status') == 'akan_datang' ? 'selected' : '' }}>Akan Datang</option>
                        <option value="selesai" class="bg-[#121212] text-white" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-2">
                    <button type="submit" aria-label="Cari kegiatan" class="w-full h-full bg-amber-500 hover:bg-amber-400 text-black font-bold rounded-xl transition-colors flex items-center justify-center">
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
                            <button onclick="shareItem('{{ $item->nama_kegiatan }}', '{{ route('agenda.show', $item->id) }}')" aria-label="Bagikan kegiatan ini" class="p-2 hover:bg-white/10 rounded-full transition-colors text-gray-400 hover:text-white" title="Bagikan">
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

@endsection

@push('scripts')
<script>
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
</script>
@endpush
