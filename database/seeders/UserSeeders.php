<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Absensi Rapat',
            'nomor_induk' => '12345678',
            'email' => 'admin@rapat.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
