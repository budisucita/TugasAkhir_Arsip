@extends('layouts.public')

@section('title', 'Kebijakan Privasi - ' . ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

    <section class="relative pt-32 pb-24">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
                    Kebijakan <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500">Privasi</span>
                </h1>
                <p class="text-gray-400 leading-relaxed">
                    Komitmen kami dalam menjaga dan melindungi data pribadi seluruh pengguna portal arsip ini.
                </p>
            </div>

            <div class="glass-panel rounded-3xl p-6 md:p-10 space-y-8 text-gray-300 leading-relaxed" data-aos="fade-up" data-aos-delay="100">
                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">1. Informasi yang Kami Kumpulkan</h2>
                    <p>Kami dapat mengumpulkan informasi yang Anda berikan secara langsung, seperti nama, alamat surel, dan data lain yang diperlukan untuk keperluan administrasi serta komunikasi kegiatan.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">2. Penggunaan Informasi</h2>
                    <p>Informasi yang dikumpulkan digunakan untuk mengelola arsip kegiatan, dokumentasi, serta menyampaikan informasi seputar agenda dan aktivitas banjar kepada pengguna yang berkepentingan.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">3. Keamanan Data</h2>
                    <p>Kami berupaya menerapkan langkah pengamanan yang wajar untuk melindungi data dari akses, perubahan, maupun penyalahgunaan oleh pihak yang tidak berwenang.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">4. Berbagi Informasi</h2>
                    <p>Kami tidak menjual atau menyewakan data pribadi kepada pihak ketiga. Data hanya dibagikan apabila diwajibkan oleh ketentuan hukum yang berlaku.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">5. Perubahan Kebijakan</h2>
                    <p>Kebijakan privasi ini dapat diperbarui sewaktu-waktu. Perubahan akan ditampilkan pada halaman ini agar pengguna selalu memperoleh informasi terbaru.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">6. Hubungi Kami</h2>
                    <p>Apabila Anda memiliki pertanyaan mengenai kebijakan privasi ini, silakan menghubungi kami melalui halaman <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300 font-medium">Kontak</a>.</p>
                </div>
            </div>
        </div>
    </section>

@endsection
