<?php

use App\Models\Peserta;
use App\Models\Rapat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access dashboard', function () {
    $this->getJson('/api/v1/dashboard')
        ->assertStatus(401);
});

test('admin can access dashboard and see global metrics', function () {
    $admin = User::factory()->admin()->create();

    // Create a meeting in the current month
    $meetingThisMonth = Rapat::factory()->create([
        'waktu_mulai' => now()->startOfMonth()->addDays(2),
        'waktu_selesai' => now()->startOfMonth()->addDays(2)->addHours(2),
        'status' => 'dijadwalkan',
    ]);

    // Create an upcoming meeting
    $upcomingMeeting = Rapat::factory()->create([
        'waktu_mulai' => now()->addDays(5),
        'waktu_selesai' => now()->addDays(5)->addHours(2),
        'status' => 'dijadwalkan',
    ]);

    // Add participants to meeting
    Peserta::factory()->create([
        'rapat_id' => $meetingThisMonth->id,
        'status_kehadiran' => 'hadir',
    ]);
    Peserta::factory()->create([
        'rapat_id' => $meetingThisMonth->id,
        'status_kehadiran' => 'belum_hadir',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'total_bulan_ini',
                'total_upcoming',
                'bulan_ini_persen',
            ]
        ]);
});

test('teacher can access dashboard and see personal scoped metrics', function () {
    $teacher1 = User::factory()->guru()->create();
    $teacher2 = User::factory()->guru()->create();

    // Rapat 1: Diikuti Guru 1
    $rapat1 = Rapat::factory()->create([
        'waktu_mulai' => now()->startOfMonth()->addDays(1),
        'waktu_selesai' => now()->startOfMonth()->addDays(1)->addHours(2),
    ]);
    Peserta::factory()->create([
        'rapat_id' => $rapat1->id,
        'user_id' => $teacher1->id,
        'status_kehadiran' => 'hadir',
    ]);

    // Rapat 2: Diikuti Guru 2 (Guru 1 tidak ikut)
    $rapat2 = Rapat::factory()->create([
        'waktu_mulai' => now()->startOfMonth()->addDays(2),
        'waktu_selesai' => now()->startOfMonth()->addDays(2)->addHours(2),
    ]);
    Peserta::factory()->create([
        'rapat_id' => $rapat2->id,
        'user_id' => $teacher2->id,
        'status_kehadiran' => 'hadir',
    ]);

    // Rapat 3: Mendatang, Guru 1 ikut
    $rapat3 = Rapat::factory()->create([
        'waktu_mulai' => now()->addDays(3),
        'waktu_selesai' => now()->addDays(3)->addHours(2),
        'status' => 'dijadwalkan',
    ]);
    Peserta::factory()->create([
        'rapat_id' => $rapat3->id,
        'user_id' => $teacher1->id,
        'status_kehadiran' => 'belum_hadir',
    ]);

    // Test Guru 1 Dashboard
    $response1 = $this->actingAs($teacher1)
        ->getJson('/api/v1/dashboard');

    $response1->assertStatus(200)
        ->assertJsonPath('data.total_bulan_ini', 2) // Rapat 1 and Rapat 3 are in this month for teacher 1
        ->assertJsonPath('data.total_upcoming', 1) // Rapat 3 is upcoming for teacher 1
        ->assertJsonPath('data.bulan_ini_persen', 50); // Teacher 1 attended 1 out of 2 meetings (50%)

    // Test Guru 2 Dashboard
    $response2 = $this->actingAs($teacher2)
        ->getJson('/api/v1/dashboard');

    $response2->assertStatus(200)
        ->assertJsonPath('data.total_bulan_ini', 1) // Only Rapat 2 is in this month for teacher 2
        ->assertJsonPath('data.total_upcoming', 0); // No upcoming meetings for teacher 2
});
