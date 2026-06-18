<?php

namespace App\Http\Controllers;

use App\Models\Rapat;
use Illuminate\Http\JsonResponse;

class LaporanController extends Controller
{
    /**
     * Mengambil statistik kehadiran dan notulen rapat untuk Frontend
     */
    public function ambilLaporan(Rapat $rapat): JsonResponse
    {
        // 1. Hitung total peserta di rapat tersebut
        $totalPeserta = $rapat->pesertas()->count();

        // 2. Hitung agregat berdasarkan status kehadiran
        $hadir = $rapat->pesertas()->where('status_kehadiran', 'hadir')->count();
        $izin  = $rapat->pesertas()->where('status_kehadiran', 'izin')->count();
        $sakit = $rapat->pesertas()->where('status_kehadiran', 'sakit')->count();
        $alfa  = $rapat->pesertas()->where('status_kehadiran', 'belum_hadir')->count();

        // 3. Hitung persentase kehadiran (jika ada peserta)
        $persenKehadiran = $totalPeserta > 0 ? round(($hadir / $totalPeserta) * 100, 2) : 0;

        return response()->json([
            'status'  => 'success',
            'message' => 'Laporan agregat rapat berhasil didapatkan.',
            'data'    => [
                'rapat_info' => [
                    'judul' => $rapat->judul,
                    'tanggal' => $rapat->waktu_mulai?->toIso8601String(),
                ],
                'statistik' => [
                    'total_peserta'     => $totalPeserta,
                    'jumlah_hadir'      => $hadir,
                    'jumlah_izin'       => $izin,
                    'jumlah_sakit'      => $sakit,
                    'jumlah_tanpa_keterangan' => $alfa,
                    'persentase_kehadiran'    => $persenKehadiran . '%',
                ],
                // Sekalian panggil data notulen jika sudah diisi oleh admin
                'notulen' => $rapat->notulen ?? null 
            ]
        ], 200);
    }
}
