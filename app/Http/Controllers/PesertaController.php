<?php

namespace App\Http\Controllers;

use App\Models\Rapat;
use App\Models\Peserta;
use App\Models\User;
use App\Http\Requests\Peserta\StorePesertaRequest;
use App\Http\Resources\PesertaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
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
        $userId = Auth::id();

        // Cari record peserta
        $peserta = Peserta::where('rapat_id', $rapat->id)
            ->where('user_id', $userId)
            ->first();

        if (!$peserta) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak diundang ke rapat ini.',
            ], 403);
        }

        // Jika sudah join sebelumnya
        if ($peserta->waktu_join) {
            return (new PesertaResource($peserta->load('user')))
                ->additional([
                    'status'  => 'success',
                    'message' => 'Anda sudah tercatat hadir di rapat ini sebelumnya.',
                ])
                ->response()
                ->setStatusCode(200);
        }

        // Catat waktu hadir sekarang
        $peserta->update([
            'waktu_join' => now(),
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
}
