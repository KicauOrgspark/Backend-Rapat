<?php

namespace Database\Factories;

use App\Models\Rapat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rapat>
 */
class RapatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $waktuMulai = now()->addDays(rand(1, 10))->setTime(rand(8, 16), 0);
        $waktuSelesai = (clone $waktuMulai)->addHours(rand(1, 3));

        return [
            'user_id'       => User::factory(),
            'judul'         => fake()->sentence(4),
            'deskripsi'     => fake()->paragraph(),
            'waktu_mulai'   => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'lokasi'        => fake()->randomElement(['Ruang Guru', 'Aula Sekolah', 'Lab Komputer']),
            'link_rapat'    => fake()->optional()->url(),
            'status'        => 'dijadwalkan',
            'image_path'    => null,
        ];
    }
}
