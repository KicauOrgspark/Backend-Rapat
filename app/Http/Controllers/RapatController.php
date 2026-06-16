<?php

namespace App\Http\Controllers;

use App\Models\Rapat;
use App\Http\Requests\Rapat\StoreRapatRequest;
use App\Http\Requests\Rapat\UpdateRapatRequest;
use App\Http\Resources\RapatResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class RapatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $rapats = Rapat::with(['pembuat', 'pesertas'])->latest()->paginate(10);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar rapat berhasil diambil.',
            'meta'    => [
                'current_page' => $rapats->currentPage(),
                'last_page'    => $rapats->lastPage(),
                'per_page'     => $rapats->perPage(),
                'total'        => $rapats->total(),
            ],
            'data'    => RapatResource::collection($rapats),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRapatRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['user_id'] = Auth::id();

        // Upload file banner rapat jika ada
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('rapats/banners', 'public');
        }

        // Proses Create ke database
        $rapat = Rapat::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal rapat berhasil dibuat.',
            'data'    => new RapatResource($rapat->load(['pembuat', 'pesertas'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rapat $rapat): JsonResponse
    {
        // Route Model Binding otomatis melempar 404 jika ID tidak valid
        return response()->json([
            'status'  => 'success',
            'message' => 'Detail rapat berhasil ditemukan.',
            'data'    => new RapatResource($rapat->load(['pembuat', 'pesertas'])),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRapatRequest $request, Rapat $rapat): JsonResponse
    {
        // Validasi aman dari jebakan partial update via UpdateRapatRequest
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($rapat->image_path) {
                Storage::disk('public')->delete($rapat->image_path);
            }
            $data['image_path'] = $request->file('image')->store('rapats/banners', 'public');
        }

        $rapat->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal rapat berhasil diperbarui.',
            'data'    => new RapatResource($rapat->load(['pembuat', 'pesertas'])),
        ], 200);
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
