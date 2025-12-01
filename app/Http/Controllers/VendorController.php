<?php

namespace App\Http\Controllers;

use App\Models\Vendors;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $Vendor = Vendors::all();
        return response()->json([
            'success' => true,
            'data' => $Vendor
        ]);
    }

    // Create Vendor
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_number' => 'required|unique:vendor',
            'vendor_name' => 'required|string',
            'phone_number' => 'somtimes|string',
            'emails' => 'sometimes|email',
        ]);
        $Vendor = Vendors::create($validated);
        return response()->json([
            'success' => true,
            'data' => $Vendor
        ]);
    }

    // Show Vendor by ID
    public function show($id)
    {
        $Vendor = Vendors::find($id);
        if (!$Vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $Vendor
        ]);
    }

    // Update Vendor by ID
    public function update(Request $request, $id)
    {
        $Vendor = Vendors::find($id);
        if (!$Vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }
        $validated = $request->validate([
            'vendor_name' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
            'emails' => 'sometimes|email',
        ]);
        $Vendor->update($validated);
        return response()->json([
            'success' => true,
            'data' => $Vendor
        ]);
    }

    // delete
    public function destroy($id)
    {
        Vendors::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material deleted successfully'
        ]);
    }

    // All materials from vendor
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
