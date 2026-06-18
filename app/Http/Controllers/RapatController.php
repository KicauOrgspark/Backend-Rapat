<?php

namespace App\Http\Controllers;

use App\Models\Rapat;
use App\Models\Peserta;
use App\Models\User;
use App\Http\Requests\Rapat\StoreRapatRequest;
use App\Http\Requests\Rapat\UpdateRapatRequest;
use App\Http\Resources\RapatResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class RapatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $rapats = Rapat::with(['pembuat', 'pesertas'])->latest()->paginate(10);

        // Laravel otomatis membungkus link & meta pagination bawaan!
        return RapatResource::collection($rapats)->additional([
            'status'  => 'success',
            'message' => 'Daftar rapat berhasil diambil.',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRapatRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        // Auto-fill waktu_selesai jika tidak dikirim (default 2 jam setelah waktu_mulai)
        if (empty($data['waktu_selesai'])) {
            $data['waktu_selesai'] = date('Y-m-d H:i:s', strtotime($data['waktu_mulai'] . ' + 2 hours'));
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('rapats/banners', 'public');
        }

        $rapat = Rapat::create($data);

        // Secara otomatis mendaftarkan semua user ber-role 'guru' sebagai peserta rapat
        $gurus = User::where('role', 'guru')->get();
        foreach ($gurus as $guru) {
            Peserta::firstOrCreate([
                'rapat_id' => $rapat->id,
                'user_id'  => $guru->id,
            ]);
        }

        return (new RapatResource($rapat->load(['pembuat', 'pesertas'])))
            ->additional([
                'status'  => 'success',
                'message' => 'Jadwal rapat berhasil dibuat dan otomatis didistribusikan ke seluruh guru.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rapat $rapat): RapatResource
    {
        return (new RapatResource($rapat->load(['pembuat', 'pesertas'])))->additional([
            'status'  => 'success',
            'message' => 'Detail rapat berhasil ditemukan.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRapatRequest $request, Rapat $rapat): RapatResource
    {
        $data = $request->validated();

        // Sesuaikan waktu_selesai jika waktu_mulai berubah tapi waktu_selesai tidak dikirim
        if (isset($data['waktu_mulai']) && empty($data['waktu_selesai'])) {
            $originalMulai = $rapat->waktu_mulai;
            $originalSelesai = $rapat->waktu_selesai;
            $durationMinutes = $originalMulai->diffInMinutes($originalSelesai);
            
            $data['waktu_selesai'] = date('Y-m-d H:i:s', strtotime($data['waktu_mulai'] . ' + ' . $durationMinutes . ' minutes'));
        }

        if ($request->hasFile('image')) {
            if ($rapat->image_path) {
                Storage::disk('public')->delete($rapat->image_path);
            }
            $data['image_path'] = $request->file('image')->store('rapats/banners', 'public');
        }

        $rapat->update($data);

        return (new RapatResource($rapat->load(['pembuat', 'pesertas'])))->additional([
            'status'  => 'success',
            'message' => 'Jadwal rapat berhasil diperbarui.',
        ]);
    }

    /**
     * Get meetings where the authenticated user is a participant.
     */
    public function myMeetings(): AnonymousResourceCollection
    {
        $user = Auth::user();

        $rapats = Rapat::whereHas('pesertas', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['pembuat', 'pesertas'])
        ->latest()
        ->paginate(10);

        return RapatResource::collection($rapats)->additional([
            'status'  => 'success',
            'message' => 'Daftar rapat Anda berhasil diambil.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rapat $rapat): JsonResponse
    {
        if ($rapat->image_path) {
            Storage::disk('public')->delete($rapat->image_path);
        }

        $rapat->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal rapat berhasil dihapus dari sistem.',
        ], 200);
    }
}
