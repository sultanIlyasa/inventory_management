<?php

namespace App\Exports;

use App\Services\StatusChangeService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StatusChangeLogExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly array $filters,
        private readonly ?string $fromStatus = null,
        private readonly ?string $toStatus = null,
    ) {
    }

    public function collection()
    {
        return app(StatusChangeService::class)->getStatusChangeLogs(
            $this->filters,
            $this->fromStatus,
            $this->toStatus
        );
    }

    public function headings(): array
    {
        return [
            'Change Date',
            'Previous Date',
            'Material Number',
            'Description',
            'PIC',
            'Usage',
            'Location',
            'Gentan-I',
            'Previous Status',
            'New Status',
            'Previous Stock',
            'New Stock',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date,
            $row->prev_date,
            $row->material_number,
            $row->description,
            $row->pic_name,
            $row->usage,
            $row->location,
            $row->gentani,
            $row->prev_status,
            $row->status,
            $row->prev_daily_stock,
            $row->daily_stock,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
