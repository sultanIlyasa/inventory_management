<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class DiscrepancyTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        // Return empty collection or example data
        return new Collection([
            ['RM-2024-001', 50, 0, 0, 0, 150000],
            ['PK-2024-055', 1200, 0, -100, 0, 5000],
            ['EL-2024-889', 45, 5, 0, 0, 75000],
        ]);
    }

    public function headings(): array
    {
        return [
            'Material Number',
            'SoH',
            'Outstanding GR',
            'Outstanding GI',
            'Error Moving',
            'Price',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ],
            ],
        ];
    }
}
