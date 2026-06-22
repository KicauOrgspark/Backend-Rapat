<?php

namespace App\Exports;

use App\Exports\SummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceReportExport implements WithMultipleSheets
{
    public function __construct(
        private int $bulan,
        private int $tahun,
        private ?string $namaRapat = null
    ) {}

    public function sheets(): array
    {
        return [
            new SummarySheet(
                $this->bulan,
                $this->tahun,
                $this->namaRapat
            ),

            new MonthlyAttendanceSheet(
                $this->bulan,
                $this->tahun,
                $this->namaRapat
            ),

            new AttendanceDetailSheet(
                $this->bulan,
                $this->tahun,
                $this->namaRapat
            ),
        ];
    }
}