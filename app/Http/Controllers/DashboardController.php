<?php

namespace App\Http\Controllers;

use App\Models\Rapat;
use App\Http\Resources\RapatResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary metrics for meetings and attendance.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $userId = $user->id;
        $isAdmin = $user->isAdmin();

        // 1. RAPAT BULAN INI VS BULAN LALU
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        if ($isAdmin) {
            $totalBulanIni = Rapat::whereBetween('waktu_mulai', [$startOfMonth, $endOfMonth])->count();
            $totalBulanLalu = Rapat::whereBetween('waktu_mulai', [$startOfLastMonth, $endOfLastMonth])->count();
        } else {
            $totalBulanIni = Rapat::whereHas('pesertas', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->whereBetween('waktu_mulai', [$startOfMonth, $endOfMonth])->count();

            $totalBulanLalu = Rapat::whereHas('pesertas', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->whereBetween('waktu_mulai', [$startOfLastMonth, $endOfLastMonth])->count();
        }

        $perubahanPersen = 0.00;
        if ($totalBulanLalu > 0) {
            $perubahanPersen = round((($totalBulanIni - $totalBulanLalu) / $totalBulanLalu) * 100, 2);
        } else if ($totalBulanIni > 0) {
            $perubahanPersen = 100.00;
        }

        // 2. RAPAT MENDATANG
        if ($isAdmin) {
            $upcomingQuery = Rapat::where('waktu_mulai', '>=', now())
                ->whereIn('status', ['dijadwalkan', 'berlangsung'])
                ->orderBy('waktu_mulai', 'asc');
        } else {
            $upcomingQuery = Rapat::whereHas('pesertas', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where('waktu_mulai', '>=', now())
                ->whereIn('status', ['dijadwalkan', 'berlangsung'])
                ->orderBy('waktu_mulai', 'asc');
        }

        $totalUpcoming = $upcomingQuery->count();
        $upcomingMeetings = $upcomingQuery->with(['pembuat', 'pesertas'])->take(5)->get();

        // 3. RATA-RATA KEHADIRAN PER BULAN (6 Bulan Terakhir)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        if ($isAdmin) {
            $meetings = Rapat::where('waktu_mulai', '>=', $sixMonthsAgo)
                ->withCount([
                    'pesertas as total_peserta',
                    'pesertas as total_hadir' => function ($query) {
                        $query->where('status_kehadiran', 'hadir');
                    }
                ])
                ->get();
        } else {
            $meetings = Rapat::whereHas('pesertas', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where('waktu_mulai', '>=', $sixMonthsAgo)
                ->withCount([
                    'pesertas as total_peserta' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                    'pesertas as total_hadir' => function ($query) use ($userId) {
                        $query->where('user_id', $userId)->where('status_kehadiran', 'hadir');
                    }
                ])
                ->get();
        }

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $grouped = $meetings->groupBy(function ($meeting) {
            return $meeting->waktu_mulai->format('Y-m');
        });

        $riwayatKehadiran = [];
        $currentMonthStr = now()->format('Y-m');
        $currentMonthKehadiranPersen = 0.00;

        foreach ($months as $monthStr) {
            $meetingsInMonth = $grouped->get($monthStr, collect());
            
            $totalPeserta = 0;
            $totalHadir = 0;
            
            foreach ($meetingsInMonth as $meeting) {
                $totalPeserta += $meeting->total_peserta;
                $totalHadir += $meeting->total_hadir;
            }

            $rataRataKehadiranPersen = $totalPeserta > 0
                ? round(($totalHadir / $totalPeserta) * 100, 2)
                : 0.00;

            $date = Carbon::parse($monthStr . '-01');
            $bulanIndo = $indonesianMonths[$date->month] . ' ' . $date->year;

            $riwayatKehadiran[] = [
                'bulan' => $bulanIndo,
                'formatted_month' => $monthStr,
                'total_rapat' => $meetingsInMonth->count(),
                'rata_rata_kehadiran_persen' => $rataRataKehadiranPersen,
            ];

            if ($monthStr === $currentMonthStr) {
                $currentMonthKehadiranPersen = $rataRataKehadiranPersen;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'total_bulan_ini' => $totalBulanIni,
                'total_upcoming' => $totalUpcoming,
                'bulan_ini_persen' => $currentMonthKehadiranPersen,
            ]
        ], 200);
    }
}
