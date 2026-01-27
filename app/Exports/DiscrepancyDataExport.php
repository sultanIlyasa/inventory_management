<?php

namespace App\Exports;

use App\Models\DiscrepancyMaterials;
use App\Models\DailyInput;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class DiscrepancyDataExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DiscrepancyMaterials::with(['material']);

        // Apply filters
        if (!empty($this->filters['location'])) {
            $query->whereHas('material', function ($q) {
                $q->where('location', $this->filters['location']);
            });
        }
        if (!empty($this->filters['pic'])) {
            $query->whereHas('material', function ($q) {
                $q->where('pic_name', $this->filters['pic']);
            });
        }
        if (!empty($this->filters['usage'])) {
            $query->whereHas('material', function ($q) {
                $q->where('usage', $this->filters['usage']);
            });
        }
        if (!empty($this->filters['search'])) {
            $searchTerm = $this->filters['search'];
            $query->whereHas('material', function ($q) use ($searchTerm) {
                $q->where('material_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        $discrepancies = $query->get();

        return $discrepancies->map(function ($discrepancy) {
            $material = $discrepancy->material;
            $latestInput = DailyInput::getLatestForMaterial($discrepancy->material_id);

            $qtyActual = $latestInput ? $latestInput->daily_stock : 0;
            $sohTimestamp = $discrepancy->last_synced_at ? $discrepancy->last_synced_at->format('Y-m-d H:i') : '';
            $qtyActualTimestamp = $latestInput ? $latestInput->updated_at->format('Y-m-d H:i') : '';

            $initialDiscrepancy = $qtyActual - $discrepancy->soh;
            $explained = $discrepancy->outstanding_gr + $discrepancy->outstanding_gi + $discrepancy->error_moving;
            $finalDiscrepancy = $initialDiscrepancy + $explained;
            $finalAmount = $finalDiscrepancy * $discrepancy->price;

            return [
                'material_number' => $material->material_number,
                'description' => $material->description,
                'location' => $material->location,
                'pic' => $material->pic_name,
                'uom' => $material->unit_of_measure,
                'price' => $discrepancy->price,
                'soh' => $discrepancy->soh,
                'soh_timestamp' => $sohTimestamp,
                'qty_actual' => $qtyActual,
                'qty_actual_timestamp' => $qtyActualTimestamp,
                'initial_gap' => $initialDiscrepancy,
                'outstanding_gr' => $discrepancy->outstanding_gr,
                'outstanding_gi' => $discrepancy->outstanding_gi,
                'error_movement' => $discrepancy->error_moving,
                'final_discrepancy' => $finalDiscrepancy,
                'final_amount' => $finalAmount,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Material Number',
            'Description',
            'Location',
            'PIC',
            'UoM',
            'Price',
            'SoH',
            'SoH Timestamp',
            'Qty Actual',
            'Qty Actual Timestamp',
            'Initial Gap',
            'Outstanding GR',
            'Outstanding GI',
            'Error Movement',
            'Final Discrepancy',
            'Final Amount',
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
