@extends('layouts.public')

@section('title', $kegiatan->nama_kegiatan . ' - ' . ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

    <main class="pt-32 pb-20 px-4 md:px-6 container mx-auto relative z-10">
        
        <!-- Breadcrumb -->
        <div class="mb-8 text-sm text-gray-400">
            <a href="{{ route('welcome') }}" class="hover:text-white transition-colors">Beranda</a>
            <span class="mx-2">/</span>
            <a href="{{ route('agenda.index') }}" class="hover:text-white transition-colors">Agenda & Kegiatan</a>
            <span class="mx-2">/</span>
            <span class="text-amber-400">{{ $kegiatan->nama_kegiatan }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Left Column: Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Main Image -->
                <div class="rounded-2xl overflow-hidden relative group shadow-2xl border border-white/10" data-aos="fade-up">
                    @if($kegiatan->dokumentasi->isNotEmpty())
                        <img src="{{ Storage::url($kegiatan->dokumentasi->first()->file_path) }}" alt="{{ $kegiatan->nama_kegiatan }}" class="w-full h-full object-cover aspect-video rounded-lg">
                    @else
                        <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                    
                    <div class="absolute top-4 right-4">
                        @if($kegiatan->status == 'Selesai')
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-green-500/20 text-green-400 border border-green-500/30 backdrop-blur-md">
                                Selesai
                            </span>
                        @elseif($kegiatan->status == 'Berlangsung')
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 backdrop-blur-md animate-pulse">
                                Berlangsung
                            </span>
                        @elseif($kegiatan->status == 'Dibatalkan')
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30 backdrop-blur-md">
                                Dibatalkan
                            </span>
                        @else
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 backdrop-blur-md">
                                Akan Datang
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Title & Meta -->
                <div data-aos="fade-up" data-aos-delay="100">
                    <h1 class="text-2xl md:text-4xl font-bold text-white mb-6 leading-tight">
                        {{ $kegiatan->nama_kegiatan }}
                    </h1>
                    
                    <div class="flex flex-wrap gap-6 text-gray-400 border-b border-white/10 pb-8 mb-8">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $kegiatan->tanggal_mulai->format('d F Y') }}</span>
                        </div>
                    </div>

                    <div class="prose prose-invert prose-lg max-w-none text-gray-300 leading-relaxed">
                        {!! $kegiatan->deskripsi !!}
                    </div>
                </div>

                <!-- Gallery Grid if exists -->
                @if($kegiatan->dokumentasi->count() > 1)
                <div class="pt-8 border-t border-white/10" data-aos="fade-up">
                    <h3 class="text-2xl font-bold text-white mb-6">Galeri Kegiatan</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @foreach($kegiatan->dokumentasi as $doc)
                            <div class="relative group rounded-2xl overflow-hidden glass-card hover:shadow-2xl hover:shadow-amber-500/10 transition-all duration-300" data-aos="fade-up">
                                <div class="w-full aspect-video relative overflow-hidden rounded-2xl">
                                    <img src="{{ Storage::url($doc->file_path) }}" 
                                         alt="Dokumentasi" 
                                         class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
                                        <div class="transform translate-y-0 md:translate-y-4 md:group-hover:translate-y-0 transition-transform duration-300">
                                            <p class="text-white font-medium text-sm line-clamp-2 mb-1">Dokumentasi</p>
                                            <p class="text-gray-400 text-xs">{{ $kegiatan->created_at->format('d M Y') }}</p>
                                        </div>
                                        <a href="{{ Storage::url($doc->file_path) }}" 
                                           target="_blank"
                                           class="absolute bottom-6 right-6 px-4 py-2 min-h-[44px] rounded-xl bg-white text-black hover:bg-amber-400 transition-colors shadow-lg transform translate-y-0 md:translate-y-4 md:group-hover:translate-y-0 transition-transform duration-300 delay-75"
                                           title="Lihat Foto">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M15 3h6v6M14 10l6.1-6.1M9 21H3v-6M10 14l-6.1 6.1"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Sidebar -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Share Card -->
                <div class="glass-panel p-6 rounded-2xl" data-aos="fade-left">
                    <h3 class="text-xl font-bold text-white mb-4">Bagikan Kegiatan</h3>
                    <div class="flex gap-2">
                        <button onclick="shareTo('whatsapp')" aria-label="Bagikan kegiatan ini ke WhatsApp" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Related Activities -->
                @if($relatedKegiatan->isNotEmpty())
                <div class="space-y-4" data-aos="fade-left" data-aos-delay="100">
                    <h3 class="text-xl font-bold text-white">Kegiatan Terkait</h3>
                    @foreach($relatedKegiatan as $related)
                    <a href="{{ route('agenda.show', $related->id) }}" class="block glass-card p-4 rounded-xl group">
                        <div class="flex gap-4">
                            <div class="w-24 h-24 rounded-lg overflow-hidden flex-shrink-0">
                                @if($related->dokumentasi->isNotEmpty())
                                    <img src="{{ Storage::disk('public')->url($related->dokumentasi->first()->file_path) }}" alt="{{ $related->nama_kegiatan }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-2 mb-2">{{ $related->nama_kegiatan }}</h4>
                                <div class="text-xs text-gray-400 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $related->tanggal_mulai->format('d M Y') }}
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </main>

@endsection

@push('scripts')
<script>
    function shareTo(platform) {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent("{{ $kegiatan->nama_kegiatan }}");
        
        let shareUrl = '';
        
        shareUrl = `https://wa.me/?text=${text}%20${url}`;
        
        if(shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
    }
</script>
@endpush
