<?php

namespace App\Exports;

use App\Models\Peserta;
use App\Models\Rapat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummarySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private int $bulan,
        private int $tahun,
        private ?string $namaRapat = null
    ) {}

    public function collection()
    {
        $rapatQuery = Rapat::query()
            ->where('status', 'selesai')
            ->whereMonth('waktu_mulai', $this->bulan)
            ->whereYear('waktu_mulai', $this->tahun);

        if ($this->namaRapat) {
            $rapatQuery->where(
                'judul',
                'like',
                '%' . $this->namaRapat . '%'
            );
        }

        $rapatIds = $rapatQuery->pluck('id');

        $attendanceQuery = Peserta::whereIn(
            'rapat_id',
            $rapatIds
        );

        return collect([
            [
                'Total Rapat',
                $rapatIds->count()
            ],
            [
                'Total Peserta',
                $attendanceQuery->count()
            ],
            [
                'Total Hadir',
                (clone $attendanceQuery)
                    ->where('status_kehadiran', 'hadir')
                    ->count()
            ],
            [
                'Total Izin',
                (clone $attendanceQuery)
                    ->where('status_kehadiran', 'izin')
                    ->count()
            ],
            [
                'Total Sakit',
                (clone $attendanceQuery)
                    ->where('status_kehadiran', 'sakit')
                    ->count()
            ],
            [
                'Total Belum Hadir',
                (clone $attendanceQuery)
                    ->where('status_kehadiran', 'belum_hadir')
                    ->count()
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Keterangan', 'Jumlah'];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }
}