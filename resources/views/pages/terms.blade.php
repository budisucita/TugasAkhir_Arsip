@extends('layouts.public')

@section('title', 'Syarat & Ketentuan - ' . ($settings ?? collect())->get('judul_website', 'ST. Dharma Bhakti'))

@section('content')

    <section class="relative pt-32 pb-24">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
                    Syarat & <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500">Ketentuan</span>
                </h1>
                <p class="text-gray-400 leading-relaxed">
                    Ketentuan penggunaan layanan portal arsip dan dokumentasi kegiatan.
                </p>
            </div>

            <div class="glass-panel rounded-3xl p-6 md:p-10 space-y-8 text-gray-300 leading-relaxed" data-aos="fade-up" data-aos-delay="100">
                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">1. Penerimaan Ketentuan</h2>
                    <p>Dengan mengakses dan menggunakan portal ini, Anda dianggap telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang berlaku.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">2. Penggunaan Layanan</h2>
                    <p>Portal ini disediakan untuk keperluan penyajian arsip kegiatan, dokumentasi, dan informasi resmi. Pengguna dilarang menggunakan layanan untuk tindakan yang melanggar hukum atau merugikan pihak lain.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">3. Hak Kekayaan Intelektual</h2>
                    <p>Seluruh konten, termasuk teks, gambar, dan dokumentasi yang ditampilkan, merupakan milik pengelola portal dan dilindungi oleh ketentuan yang berlaku. Penggunaan konten di luar keperluan yang sah harus memperoleh izin terlebih dahulu.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">4. Batasan Tanggung Jawab</h2>
                    <p>Pengelola berupaya menyajikan informasi yang akurat, namun tidak menjamin bahwa seluruh konten bebas dari kekeliruan. Pengelola tidak bertanggung jawab atas kerugian yang timbul akibat penggunaan informasi pada portal ini.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">5. Perubahan Ketentuan</h2>
                    <p>Syarat dan ketentuan ini dapat disesuaikan sewaktu-waktu tanpa pemberitahuan terlebih dahulu. Pengguna disarankan untuk meninjau halaman ini secara berkala.</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xl font-bold text-white">6. Kontak</h2>
                    <p>Untuk pertanyaan terkait syarat dan ketentuan ini, silakan menghubungi kami melalui halaman <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300 font-medium">Kontak</a>.</p>
                </div>
            </div>
        </div>
    </section>

@endsection
