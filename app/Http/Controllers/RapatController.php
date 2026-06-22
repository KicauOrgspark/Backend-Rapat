<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Http\Requests\Export\ExportRequest;
use App\Http\Requests\Rapat\StoreRapatRequest;
use App\Http\Requests\Rapat\UpdateRapatRequest;
use App\Http\Resources\PesertaResource;
use App\Http\Resources\RapatResource;
use App\Models\Peserta;
use App\Models\Rapat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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

        // Menggunakan Carbon (Lebih standar Laravel dibanding strtotime)
        if (empty($data['waktu_selesai'])) {
            $data['waktu_selesai'] = Carbon::parse($data['waktu_mulai'])->addHours(4)->toDateTimeString();
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('rapats/banners', 'public');
        }

        $rapat = Rapat::create($data);

        // OPTIMASI: Ambil ID guru saja, lalu lakukan Bulk Insert
        $guruIds = User::where('role', 'guru')->pluck('id');

        if ($guruIds->isNotEmpty()) {
            $pesertaData = $guruIds->map(function ($guruId) use ($rapat) {
                return [
                    'rapat_id'   => $rapat->id,
                    'user_id'    => $guruId,
                    'created_at' => now(), // Manual diisi karena bulk insert tidak otomatis mengisi timestamp
                    'updated_at' => now(),
                ];
            })->toArray();

            // Hanya menjalankan 1 query eksekusi ke database untuk semua guru
            Peserta::insert($pesertaData);
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
    public function update(UpdateRapatRequest $request, Rapat $rapat): JsonResponse
    {
        $data = $request->validated();

        // Sesuaikan waktu_selesai jika waktu_mulai berubah tapi waktu_selesai tidak dikirim
        if (isset($data['waktu_mulai']) && empty($data['waktu_selesai'])) {
            // AMAN: Dipastikan menjadi objek Carbon terlebih dahulu
            $originalMulai = Carbon::parse($rapat->waktu_mulai);
            $originalSelesai = Carbon::parse($rapat->waktu_selesai);

            $durationMinutes = $originalMulai->diffInMinutes($originalSelesai);

            // LEBIH BERSIH: Menggunakan Carbon untuk menambah menit
            $data['waktu_selesai'] = Carbon::parse($data['waktu_mulai'])
                ->addMinutes($durationMinutes)
                ->toDateTimeString();
        }

        // Proses penggantian gambar banner
        if ($request->hasFile('image')) {
            if ($rapat->image_path) {
                Storage::disk('public')->delete($rapat->image_path);
            }
            $data['image_path'] = $request->file('image')->store('rapats/banners', 'public');
        }

        // Update data ke database
        $rapat->update($data);

        // Mengembalikan JsonResponse agar konsisten dengan method store()
        return (new RapatResource($rapat->load(['pembuat', 'pesertas'])))
            ->additional([
                'status'  => 'success',
                'message' => 'Jadwal rapat berhasil diperbarui.',
            ])
            ->response()
            ->setStatusCode(200);
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

    public function export(ExportRequest $request)
    {
        return Excel::download(
            new AttendanceReportExport(
                $request->bulan,
                $request->tahun,
                $request->nama_rapat
            ),
            'rekap_absensi_' .
                $request->bulan .
                '_' .
                $request->tahun .
                '.xlsx'
        );
    }
}
