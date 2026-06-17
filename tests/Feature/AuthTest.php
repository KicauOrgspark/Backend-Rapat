<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'nomor_induk' => '12345678',
        'password'    => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'nomor_induk' => '12345678',
        'password'    => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
            ],
        ]);
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'nomor_induk' => '12345678',
        'password'    => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'nomor_induk' => '12345678',
        'password'    => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'nomor induk atau password salah',
        ]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logout berhasil',
        ]);
});
