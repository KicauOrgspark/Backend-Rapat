<?php

use App\Models\Rapat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access meetings', function () {
    $this->getJson('/api/v1/rapat')
        ->assertStatus(401);
});

test('authenticated teacher can list meetings', function () {
    $user = User::factory()->guru()->create();
    Rapat::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/rapat')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'judul',
                    'deskripsi',
                    'waktu_mulai',
                    'waktu_selesai',
                    'lokasi',
                    'link_rapat',
                    'status',
                    'banner_url',
                    'created_at',
                ],
            ],
            'links',
            'meta',
        ]);
});

test('teacher cannot create meeting schedules', function () {
    $user = User::factory()->guru()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/rapat', [
            'judul'         => 'Rapat Kurikulum Baru',
            'waktu_mulai'   => now()->addDay()->toDateTimeString(),
            'waktu_selesai' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthorized',
        ]);
});

test('admin can create meeting schedules', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/rapat', [
            'judul'         => 'Rapat Ujian Sekolah',
            'deskripsi'     => 'Koordinasi pelaksanaan ujian semester genap.',
            'waktu_mulai'   => now()->addDay()->toDateTimeString(),
            'waktu_selesai' => now()->addDay()->addHours(2)->toDateTimeString(),
            'lokasi'        => 'Aula Utama',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.judul', 'Rapat Ujian Sekolah')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'judul',
                'deskripsi',
                'waktu_mulai',
                'waktu_selesai',
                'dibuat_oleh',
            ],
        ]);
});

test('admin can update meeting schedules', function () {
    $admin = User::factory()->admin()->create();
    $rapat = Rapat::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/rapat/{$rapat->id}", [
            'judul'         => 'Judul Rapat Diperbarui',
            'waktu_mulai'   => now()->addDays(2)->toDateTimeString(),
            'waktu_selesai' => now()->addDays(2)->addHours(1)->toDateTimeString(),
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.judul', 'Judul Rapat Diperbarui');
});

test('admin can update meeting status to berlangsung', function () {
    $admin = User::factory()->admin()->create();
    $rapat = Rapat::factory()->create([
        'status' => 'dijadwalkan',
    ]);

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/rapat/{$rapat->id}", [
            'status' => 'berlangsung',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'berlangsung');
});

test('admin can delete meeting schedules', function () {
    $admin = User::factory()->admin()->create();
    $rapat = Rapat::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/rapat/{$rapat->id}")
        ->assertStatus(200)
        ->assertJson([
            'status'  => 'success',
            'message' => 'Jadwal rapat berhasil dihapus dari sistem.',
        ]);

    $this->assertDatabaseMissing('rapats', ['id' => $rapat->id]);
});
