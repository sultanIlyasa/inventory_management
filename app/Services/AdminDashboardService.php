<?php

namespace App\Services;

use App\Models\Materials;
use App\Models\Vendors;
use Illuminate\Support\Collection;


class AdminDashboardService
{
    public function getAllVendors()
    {
        $Vendor = Vendors::all();
        return response()->json([
            'vendors' => $Vendor
        ]);
    }

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
