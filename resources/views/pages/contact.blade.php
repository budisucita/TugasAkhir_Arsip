@extends('layouts.public')

@section('title', 'Kontak - ' . ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

    <section class="relative pt-32 pb-24">
        <div class="container mx-auto px-4 md:px-6 max-w-4xl">
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
                    Hubungi <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500">Kami</span>
                </h1>
                <p class="text-gray-400 leading-relaxed max-w-2xl mx-auto">
                    Kami terbuka untuk koordinasi, pertanyaan, maupun kerja sama terkait kegiatan dan arsip banjar.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- Alamat -->
                <div class="glass-panel rounded-2xl p-6 text-center" data-aos="fade-up">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h2 class="text-white font-bold mb-2">Alamat</h2>
                    <p class="text-gray-400 text-sm leading-relaxed">Banjar Karya Dharma Sesetan, Denpasar, Bali.</p>
                </div>

                <!-- Jam Layanan -->
                <div class="glass-panel rounded-2xl p-6 text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <h2 class="text-white font-bold mb-2">Jam Layanan</h2>
                    <p class="text-gray-400 text-sm leading-relaxed">Senin - Sabtu, 08.00 - 17.00 WITA.</p>
                </div>

                <!-- Media -->
                <div class="glass-panel rounded-2xl p-6 text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z" opacity="0"/><path d="M22 6l-10 7L2 6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                    </div>
                    <h2 class="text-white font-bold mb-2">Informasi</h2>
                    <p class="text-gray-400 text-sm leading-relaxed">Kunjungi lokasi kami untuk koordinasi langsung.</p>
                </div>
            </div>

            <div class="glass-panel rounded-3xl p-6 md:p-10 text-center" data-aos="fade-up">
                <p class="text-gray-300 leading-relaxed mb-6">
                    Untuk keperluan koordinasi kegiatan atau permintaan informasi lebih lanjut, silakan mengunjungi kami pada lokasi berikut.
                </p>
                <a href="{{ url('/#lokasi') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-full bg-amber-400 text-black font-bold hover:bg-amber-300 transition-all shadow-lg shadow-amber-500/20">
                    Lihat Lokasi
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </section>

@endsection
