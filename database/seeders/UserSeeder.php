<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kata sandi default 'password' hanya untuk pengembangan dan pengujian.
        // Di produksi seeding akun demo ini dilarang agar tidak meninggalkan
        // akun dengan kredensial yang mudah ditebak.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn(
                'UserSeeder dilewati: akun demo dengan kata sandi default '
                .'tidak boleh dibuat di lingkungan '.app()->environment().'.'
            );

            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@dharmabhakti.com'],
            [
                'name' => 'Admin Dharma Bhakti',
                'password' => Hash::make('password'), 
                'role' => 'admin',
            ]
        );

        $pengurus = User::firstOrCreate(
            ['email' => 'pengurus@dharmabhakti.com'],
            [
                'name' => 'Pengurus Contoh',
                'password' => Hash::make('password'),
                'role' => 'pengurus',
            ]
        );
    }
}
