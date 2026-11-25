import './bootstrap';

import Alpine from 'alpinejs';
import AOS from 'aos';
import Swiper from 'swiper/bundle';

// Styles bawaan library (di-bundle ke aset lokal, tanpa CDN)
import 'aos/dist/aos.css';
import 'swiper/css/bundle';

// Ekspos library secara global agar tetap dapat diakses oleh skrip halaman publik
window.Alpine = Alpine;
window.AOS = AOS;
window.Swiper = Swiper;

// Inisialisasi AOS setelah DOM siap
document.addEventListener('DOMContentLoaded', () => {
    AOS.init({
        duration: 800,
        once: true,
        offset: 100,
        easing: 'ease-out-cubic',
    });
});

Alpine.start();
