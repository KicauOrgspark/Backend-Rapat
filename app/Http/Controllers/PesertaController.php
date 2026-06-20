<?php

namespace App\Http\Controllers;

use App\Http\Requests\Peserta\StorePesertaRequest;
use App\Http\Requests\UpdateStatusKehadiranRequest;
use App\Http\Resources\PesertaResource;
use App\Models\Peserta;
use App\Models\Rapat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class PesertaController extends Controller
{
    /**
     * Display a listing of participants for a specific meeting.
     */
    public function index(Rapat $rapat): AnonymousResourceCollection
    {
        $pesertas = Peserta::with('user')
            ->where('rapat_id', $rapat->id)
            ->get();

        return PesertaResource::collection($pesertas)->additional([
            'status'  => 'success',
            'message' => 'Daftar peserta rapat berhasil diambil.',
        ]);
    }

    /**
     * Store newly created participants in storage.
     */
    public function store(StorePesertaRequest $request, Rapat $rapat): AnonymousResourceCollection
    {
        // Menyimpan peserta secara bulk dengan aman (menghindari duplikasi)
        foreach ($request->user_ids as $userId) {
            Peserta::firstOrCreate([
                'rapat_id' => $rapat->id,
                'user_id'  => $userId,
            ]);
        }

        $pesertas = Peserta::with('user')
            ->where('rapat_id', $rapat->id)
            ->whereIn('user_id', $request->user_ids)
            ->get();

        return PesertaResource::collection($pesertas)->additional([
            'status'  => 'success',
            'message' => 'Peserta berhasil ditambahkan ke rapat.',
        ]);
    }

    /**
     * Remove a participant from a meeting.
     */
    public function destroy(Rapat $rapat, User $user): JsonResponse
    {
        $deleted = Peserta::where('rapat_id', $rapat->id)
            ->where('user_id', $user->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak terdaftar sebagai peserta rapat ini.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Peserta berhasil dihapus dari rapat.',
        ], 200);
    }

    /**
     * Participant check-in (join meeting).
     */
    public function join(Rapat $rapat): JsonResponse
    {
        // 1. VALIDASI BATAS WAKTU JOIN (Maksimal 1 jam setelah rapat dimulai)
        $waktuMulai = Carbon::parse($rapat->waktu_mulai);
        $batasWaktu = $waktuMulai->copy()->addHour(); // Tambah 1 jam dari waktu mulai

        if (now()->greaterThan($batasWaktu)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Batas waktu untuk bergabung telah habis. Anda hanya bisa join maksimal 1 jam setelah rapat dimulai.',
            ], 403);
        }

        // 2. PROSES JOIN SEPERTI BIASA
        $userId = Auth::id();

        // Cari record peserta berdasarkan rapat dan user
        $peserta = Peserta::where('rapat_id', $rapat->id)
            ->where('user_id', $userId)
            ->first();

        // JIKA USER TIDAK TERDAFTAR DI UNDANGAN
        if (!$peserta) {
            $peserta = Peserta::create([
                'rapat_id'         => $rapat->id,
                'user_id'          => $userId,
                'waktu_join'       => now(),
                'status_kehadiran' => 'hadir',
            ]);

            return (new PesertaResource($peserta->load('user')))
                ->additional([
                    'status'  => 'success',
                    'message' => 'Anda berhasil bergabung ke rapat (Luar Undangan).',
                ])
                ->response()
                ->setStatusCode(200);
        }

        // --- JIKA USER SUDAH TERDAFTAR DI UNDANGAN (MASUK FILTER ABSEN) ---

        // Jika sudah join/absen sebelumnya
        if ($peserta->waktu_join) {
            return (new PesertaResource($peserta->load('user')))
                ->additional([
                    'status'  => 'success',
                    'message' => 'Anda sudah tercatat hadir di rapat ini sebelumnya.',
                ])
                ->response()
                ->setStatusCode(200);
        }

        // Catat waktu hadir sekarang untuk yang terdaftar di undangan
        $peserta->update([
            'waktu_join'       => now(),
            'status_kehadiran' => 'hadir',
        ]);

        return (new PesertaResource($peserta->load('user')))
            ->additional([
                'status'  => 'success',
                'message' => 'Kehadiran rapat berhasil dicatat.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function UpdateStatusKehadiran(Rapat $rapat, UpdateStatusKehadiranRequest $request): JsonResponse
    {
        $user = User::find($request->user_id);
        $status = $request->status;

        // Validasi status kehadiran
        if (!in_array($status, ['hadir', 'izin', 'sakit', 'belum_hadir'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Status kehadiran tidak valid.',
            ], 400);
        }

        // Cari record peserta berdasarkan rapat dan user
        $peserta = Peserta::where('rapat_id', $rapat->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$peserta) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak terdaftar sebagai peserta rapat ini.',
            ], 404);
        }

        // Update status kehadiran
        $peserta->update(['status_kehadiran' => $status]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status kehadiran berhasil diperbarui.',
            'data'    => new PesertaResource($peserta->load('user')),
        ], 200);
    }
}
