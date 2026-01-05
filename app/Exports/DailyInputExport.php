<?php

namespace App\Exports;

use App\Models\DailyInput;
use App\Models\Materials;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DailyInputExport implements FromCollection, WithHeadings
{
    protected $date;
    protected $usage;
    protected $location;

    public function __construct($date = null, $usage = null, $location = null)
    {
        $this->date = $date ?? Carbon::today()->toDateString();
        $this->usage = $usage;
        $this->location = $location;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Get latest daily input per material up to the specified date
        $latestInputIds = DailyInput::query()
            ->select(DB::raw('MAX(id) as id'))
            ->where('date', '<=', $this->date)
            ->groupBy('material_id')
            ->pluck('id');

        // Fetch the latest inputs with material details
        $checkedItems = DailyInput::query()
            ->with('material')
            ->whereIn('id', $latestInputIds)
            ->when($this->usage, function ($query) {
                $query->whereHas('material', fn($q) => $q->where('usage', $this->usage));
            })
            ->when($this->location, function ($query) {
                $query->whereHas('material', fn($q) => $q->where('location', $this->location));
            })
            ->get()
            ->map(function ($input) {
                return [
                    'material_number' => $input->material->material_number ?? '-',
                    'description' => $input->material->description ?? '-',
                    'pic_name' => $input->material->pic_name ?? '-',
                    'UoM' => $input->material->unit_of_measure ?? '-',
                    'location' => $input->material->location ?? '-',
                    'usage' => $input->material->usage ?? '-',
                    'rack_address' => $input->material->rack_address ?? '-',
                    'min_stock' => $input->material->stock_minimum ?? 0,
                    'max_stock' => $input->material->stock_maximum ?? 0,
                    'SoH' => $input->daily_stock,
                    'last_updated' => $input->updated_at->toDateString(),
                    'status' => $input->status,
                ];
            });

        // Get checked material IDs
        $checkedMaterialIds = DailyInput::query()
            ->whereIn('id', $latestInputIds)
            ->pluck('material_id');

        // Get missing/unchecked materials
        $missingItems = Materials::query()
            ->when($this->usage, fn($q) => $q->where('usage', $this->usage))
            ->when($this->location, fn($q) => $q->where('location', $this->location))
            ->whereNotIn('id', $checkedMaterialIds)
            ->get()
            ->map(function ($material) {
                return [
                    'material_number' => $material->material_number ?? '-',
                    'description' => $material->description ?? '-',
                    'pic_name' => $material->pic_name ?? '-',
                    'UoM' => $material->unit_of_measure ?? '-',
                    'location' => $material->location ?? '-',
                    'usage' => $material->usage ?? '-',
                    'rack_address' => $material->rack_address ?? '-',
                    'min_stock' => $material->stock_minimum ?? 0,
                    'max_stock' => $material->stock_maximum ?? 0,
                    'SoH' => '-',
                    'last_updated' => '-',
                    'status' => 'UNCHECKED',
                ];
            });

        // Merge checked and missing items
        return $checkedItems->merge($missingItems);
    }

    public function headings(): array
    {
        return [
            'material_number',
            'description',
            'pic_name',
            'UoM',
            'location',
            'usage',
            'rack_address',
            'min_stock',
            'max_stock',
            'SoH',
            'last_updated',
            'status',
        ];
    }
}
