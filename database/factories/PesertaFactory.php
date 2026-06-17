<?php

namespace Database\Factories;

use App\Models\Peserta;
use App\Models\Rapat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peserta>
 */
class PesertaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rapat_id'   => Rapat::factory(),
            'user_id'    => User::factory(),
            'waktu_join' => null,
        ];
    }

    /**
     * Indicate that the participant has joined/checked in.
     */
    public function joined(): static
    {
        return $this->state(fn (array $attributes) => [
            'waktu_join' => now(),
        ]);
    }
}
