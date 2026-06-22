<?php

namespace App\Exports;

use App\Models\Peserta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceDetailSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private int $bulan,
        private int $tahun,
        private ?string $namaRapat = null
    ) {}

    public function collection()
    {
        $query = Peserta::with([
            'user',
            'rapat'
        ]);

        $query->whereHas('rapat', function ($q) {

            $q->where('status', 'selesai');

            $q->whereMonth(
                'waktu_mulai',
                $this->bulan
            );

            $q->whereYear(
                'waktu_mulai',
                $this->tahun
            );

            if ($this->namaRapat) {
                $q->where(
                    'judul',
                    'like',
                    '%' . $this->namaRapat . '%'
                );
            }
        });

        return $query->get()
            ->map(function ($attendance) {

                return [
                    'nama_rapat' =>
                        $attendance->rapat->judul,

                    'tanggal' =>
                        $attendance->rapat->waktu_mulai->format('Y-m-d'),

                    'nama_peserta' =>
                        $attendance->user->name,

                    'status' =>
                        ucfirst($attendance->status_kehadiran),

                    'waktu_absen' =>
                        $attendance->waktu_join?->format('H:i:s') ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nama Rapat',
            'Tanggal',
            'Nama Peserta',
            'Status',
            'Waktu Absen'
        ];
    }

    public function title(): string
    {
        return 'Detail Kehadiran';
    }
}