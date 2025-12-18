<?php

namespace App\Services;

use App\Models\Materials;
use App\Models\Vendors;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;



class AdminDashboardService
{
    public function getAllVendors()
    {
        $Vendor = Vendors::all();
        return response()->json([
            'vendors' => $Vendor
        ]);
    }

    public function buildVendorData(): array
    {
        return Vendors::withCount('materials')
            ->paginate(10)
            ->map(function ($vendor) {
                return [
                    'vendor' => [
                        'id' => $vendor->id,
                        'vendor_number' => $vendor->vendor_number,
                        'vendor_name' => $vendor->vendor_name,
                        'contact_person' => $vendor->contact_person ?? null,
                        'phone_number' => $vendor->phone_number,
                        'emails' => $vendor->emails,
                    ],
                    'materials' => $vendor->materials->map(function ($material) {
                        return [
                            'id' => $material->id,
                            'material_number' => $material->material_number,
                            'description' => $material->description,
                            'usage' => $material->usage,
                            'location' => $material->location,
                            'current_status' => $material->current_status ?? null,
                            'gentani' => $material->gentani,
                            'stock_minimum' => $material->stock_minimum,
                            'stock_maximum' => $material->stock_maximum,
                            'rack_address' => $material->rack_address,
                            'unit_of_measure' => $material->unit_of_measure,
                            'pic_name' => $material->pic_name
                        ];
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function buildMaterialData()
    {
        return Materials::orderby('id')->paginate(15)->toArray();
    }

    public function searchMaterials(
        ?string $keyword,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Materials::query()
            ->with('vendor:id,vendor_number,vendor_name')
            ->when($keyword, function ($query) use ($keyword) {
                $like = '%' . trim($keyword) . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('materials.material_number', 'like', $like)
                        ->orWhere('materials.description', 'like', $like)
                        ->orWhere('materials.pic_name', 'like', $like)
                        ->orWhere('materials.rack_address', 'like', $like)
                        ->orWhere('materials.location', 'like', $like);
                })
                    ->orWhereHas('vendor', function ($q) use ($like) {
                        $q->where('vendor_number', 'like', $like)
                            ->orWhere('vendor_name', 'like', $like);
                    });
            })
            ->orderBy('materials.id')
            ->paginate($perPage)
            ->withQueryString();
    }
    public function vendorAttach() {}
    public function vendorless() {}

    public function materialsByVendor($vendor_number)
    {
        $Vendor = Vendors::where('vendor_number', $vendor_number)->first();
        if (!$Vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }
        $materials = $Vendor->materials;
        return response()->json([
            'success' => true,
            'data' => $materials
        ]);
    }
}
