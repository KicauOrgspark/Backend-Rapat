<?php

namespace App\Exports;

use App\Models\Rapat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlyAttendanceSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private int $bulan,
        private int $tahun,
        private ?string $namaRapat = null
    ) {}

    public function collection()
    {
        $query = Rapat::with('pesertas')
            ->where('status', 'selesai')
            ->whereMonth('waktu_mulai', $this->bulan)
            ->whereYear('waktu_mulai', $this->tahun);

        if ($this->namaRapat) {
            $query->where(
                'judul',
                'like',
                '%' . $this->namaRapat . '%'
            );
        }

        return $query->get()->map(function ($rapat) {

            return [
                'nama_rapat' => $rapat->judul,
                'tanggal' => $rapat->waktu_mulai->format('Y-m-d'),

                'total_peserta' =>
                    $rapat->pesertas->count(),

                'hadir' =>
                    $rapat->pesertas
                        ->where('status_kehadiran', 'hadir')
                        ->count(),

                'izin' =>
                    $rapat->pesertas
                        ->where('status_kehadiran', 'izin')
                        ->count(),

                'sakit' =>
                    $rapat->pesertas
                        ->where('status_kehadiran', 'sakit')
                        ->count(),

                'belum_hadir' =>
                    $rapat->pesertas
                        ->where('status_kehadiran', 'belum_hadir')
                        ->count(),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama Rapat',
            'Tanggal',
            'Total Peserta',
            'Hadir',
            'Izin',
            'Sakit',
            'Belum Hadir'
        ];
    }

    public function title(): string
    {
        return 'Rekap Bulanan';
    }
}