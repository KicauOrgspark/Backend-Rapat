<?php

use App\Models\Rapat;
use App\Models\User;
use App\Models\Peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can assign multiple participants to a meeting', function () {
    $admin = User::factory()->admin()->create();
    $rapat = Rapat::factory()->create();
    $teachers = User::factory()->guru()->count(3)->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/rapat/{$rapat->id}/peserta", [
            'user_ids' => $teachers->pluck('id')->toArray(),
        ]);

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'rapat_id',
                    'waktu_join',
                    'hadir',
                    'user',
                ],
            ],
        ]);

    foreach ($teachers as $teacher) {
        $this->assertDatabaseHas('pesertas', [
            'rapat_id' => $rapat->id,
            'user_id'  => $teacher->id,
        ]);
    }
});

test('teacher can view their own invited meetings', function () {
    $teacher = User::factory()->guru()->create();
    
    // Rapat 1: Guru diundang
    $rapat1 = Rapat::factory()->create();
    Peserta::factory()->create([
        'rapat_id' => $rapat1->id,
        'user_id'  => $teacher->id,
    ]);

    // Rapat 2: Guru tidak diundang
    Rapat::factory()->create();

    $response = $this->actingAs($teacher)
        ->getJson('/api/v1/my-meetings');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $rapat1->id);
});

test('teacher can join a meeting they are invited to', function () {
    $teacher = User::factory()->guru()->create();
    $rapat = Rapat::factory()->create();
    
    Peserta::factory()->create([
        'rapat_id' => $rapat->id,
        'user_id'  => $teacher->id,
        'waktu_join' => null,
    ]);

    $response = $this->actingAs($teacher)
        ->postJson("/api/v1/rapat/{$rapat->id}/join");

    $response->assertStatus(200)
        ->assertJsonPath('data.hadir', true)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'waktu_join',
                'hadir',
            ],
        ]);

    $this->assertNotNull(
        Peserta::where('rapat_id', $rapat->id)->where('user_id', $teacher->id)->first()->waktu_join
    );
});

test('teacher cannot join a meeting they are not invited to', function () {
    $teacher = User::factory()->guru()->create();
    $rapat = Rapat::factory()->create();

    $response = $this->actingAs($teacher)
        ->postJson("/api/v1/rapat/{$rapat->id}/join");

    $response->assertStatus(403)
        ->assertJson([
            'status'  => 'error',
            'message' => 'Anda tidak diundang ke rapat ini.',
        ]);
});

test('admin can remove a participant from a meeting', function () {
    $admin = User::factory()->admin()->create();
    $rapat = Rapat::factory()->create();
    $teacher = User::factory()->guru()->create();

    Peserta::factory()->create([
        'rapat_id' => $rapat->id,
        'user_id'  => $teacher->id,
    ]);

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/rapat/{$rapat->id}/peserta/{$teacher->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status'  => 'success',
            'message' => 'Peserta berhasil dihapus dari rapat.',
        ]);

    $this->assertDatabaseMissing('pesertas', [
        'rapat_id' => $rapat->id,
        'user_id'  => $teacher->id,
    ]);
});
