<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nama_kegiatan' => $this->faker->sentence,
            'status' => 'Selesai',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addDay(),
            'deskripsi' => $this->faker->paragraph,
        ];
    }
}
