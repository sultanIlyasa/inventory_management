<?php

namespace App\Http\Controllers;

use App\Models\Materials;
use App\Models\Vendors;
use App\Services\AdminDashboardService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{

    public function __construct(
        protected AdminDashboardService $adminDashboardService
    ) {}


    public function index()
    {
        return Inertia::render('Admin/index', [
            'initialVendorData' => $this->adminDashboardService->buildVendorData(),
        ]);
    }

    public function materialBulk()
    {
        return Inertia::render('Admin/MaterialBulk', ['initialMaterialsData' => $this->adminDashboardService->buildMaterialData()]);
    }
    public function searchMaterials(Request $request)
    {
        $search = $request->query('search');

        return response()->json([
            'success' => true,
            'data' => $this->adminDashboardService
                ->searchMaterials($search, 15),
        ]);
    }

    public function getAllVendorsAdminApi()
    {
        return response()->json([
            'success' => true,
            'data' => $this->buildVendorData()
        ]);
    }

    private function buildMaterialsData(): array
    {
        return  DB::table('materials')->orderBy('id')->paginate(15)->values()
            ->toArray();;
    }



    private function buildVendorData(): array
    {
        return Vendors::with('materials')
            ->get()
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


    // UPDATE VENDOR
    public function updateVendor(Request $request, $id)
    {
        $vendor = Vendors::findOrFail($id);

        $validated = $request->validate([
            'vendor_name' => 'sometimes|string',
            'vendor_number' => 'sometimes|unique:vendors,vendor_number,' . $id,
            'phone_number' => 'sometimes|string',
            'emails' => 'sometimes|array',
            'emails.*' => 'email',
        ]);

        $vendor->update($validated);

        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }

    // UPDATE MATERIAL
    public function update(Request $request, $id)
    {
        $material = Materials::findOrFail($id);

        $validated = $request->validate([
            'description' => 'sometimes|string',
            'stock_minimum' => 'sometimes|integer|min:0',
            'stock_maximum' => 'sometimes|integer|min:0',
            'unit_of_measure' => 'sometimes|string',
            'pic_name' => 'sometimes|string',
            'rack_address' => 'sometimes|string',
            'usage' => 'sometimes|in:DAILY,WEEKLY,MONTHLY',
            'location' => 'sometimes|string',
            'gentani' => 'sometimes|string',
        ]);

        $material->update($validated);

        return response()->json([
            'success' => true,
            'data' => $material
        ]);
    }

    // DELETE MATERIAL
    public function destroyMaterial($id)
    {
        $material = Materials::findOrFail($id);
        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material deleted successfully.'
        ]);
    }

    // DELETE VENDOR + DETACH MATERIALS
    public function destroyVendor($id)
    {
        DB::transaction(function () use ($id) {

            $vendor = Vendors::findOrFail($id);

            Materials::where('vendor_id', $vendor->id)
                ->update(['vendor_id' => null]);

            $vendor->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted and its materials are now vendorless.'
        ]);
    }

    public function vendorlessMaterials(Request $request)
    {
        $query = Materials::whereNull('vendor_id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('material_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('pic_name', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('material_number')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $paginated
        ]);
    }



    // STORE VENDOR
    public function vendorStore(Request $request)
    {
        $validated = $request->validate([
            'vendor_number' => 'required|unique:vendors,vendor_number',
            'vendor_name' => 'required|string',
            'contact_person' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
            'emails' => 'sometimes|array',
            'emails.*' => 'email',
        ]);

        $vendor = Vendors::create($validated);

        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }


    // STORE MATERIAL FOR A VENDOR
    public function materialStore(Request $request, $vendorId)
    {
        $validated = $request->validate([
            'material_number' => 'required|unique:materials,material_number',
            'description' => 'required|string',
            'stock_minimum' => 'required|integer|min:0',
            'stock_maximum' => 'required|integer|min:0',
            'unit_of_measure' => 'required|string',
            'pic_name' => 'required|string',
            'rack_address' => 'sometimes|string',
            'usage' => 'required|in:DAILY,WEEKLY,MONTHLY',
            'location' => 'required|string',
            'gentani' => 'required|string',
        ]);

        $validated['vendor_id'] = $vendorId;

        $material = Materials::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Material created successfully',
            'data' => $material
        ]);
    }


    // DETACH MATERIAL FROM A VENDOR
    public function removeMaterials($id)
    {
        $material = Materials::findOrFail($id);

        $material->update(['vendor_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Material detached successfully.',
            'data' => $material
        ]);
    }


    // RE-ATTACH MATERIAL TO A VENDOR
    public function attachMaterialToVendor(Request $request, $materialId)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id'
        ]);

        $material = Materials::findOrFail($materialId);

        $material->update([
            'vendor_id' => $request->vendor_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material successfully re-attached.',
            'data' => $material
        ]);
    }
}
