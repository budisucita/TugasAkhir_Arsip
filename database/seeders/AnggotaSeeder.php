<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Anggota;

class AnggotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Anggota::create([
            'nama' => 'I Wayan Gede',
            'kontak' => '081234567890',
            'status' => 'aktif',
            'alamat' => 'Jl. Raya Sesetan No. 1, Denpasar',
        ]);

        Anggota::create([
            'nama' => 'Ni Made Sari',
            'kontak' => '081234567891',
            'status' => 'aktif',
            'alamat' => 'Jl. Tukad Pakerisan No. 2, Denpasar',
        ]);
    }
}
