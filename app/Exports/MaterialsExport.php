<?php

namespace App\Exports;

use App\Models\Materials;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Materials::with('vendor')
            ->orderBy('material_number')
            ->get();
    }

    public function headings(): array
    {
        return [
            'action',
            'material_id',
            'vendor_id',
            'vendor_number',
            'vendor_name',
            'material_number',
            'description',
            'stock_minimum',
            'stock_maximum',
            'unit_of_measure',
            'rack_address',
            'usage',
            'location',
            'gentani',
            'pic_name',
        ];
    }

    public function map($material): array
    {
        return [
            '', // action column (empty by default, users can fill with "delete" to delete)
            $material->id,
            $material->vendor_id,
            $material->vendor?->vendor_number,
            $material->vendor?->vendor_name,
            $material->material_number,
            $material->description,
            $material->stock_minimum,
            $material->stock_maximum,
            $material->unit_of_measure,
            $material->rack_address,
            $material->usage,
            $material->location,
            $material->gentani,
            $material->pic_name,
        ];
    }
}
