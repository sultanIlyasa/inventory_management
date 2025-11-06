<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Materials;
use Illuminate\Http\Request;

class MaterialsController extends Controller
{
    // GET /api/materials (list all materials)
    public function index()
    {
        $materials = Materials::all();
        return response()->json([
            'success' => true,
            'data' => $materials
        ]);
    }

    // GET /api/materials/{id} (show single material)
    public function show($id)
    {
        $materials = Materials::findOrFail($id);
        return new MaterialResource($materials);
    }

    // POST /api/materials (create new material)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_number' => 'required|exists:vendor,vendor_number',
            'material_number' => 'required|unique:materials',
            'description' => 'required|string',
            'stock_minimum' => 'required|integer|min:0',
            'stock_maximum' => 'required|integer|min:0',
            'unit_of_measure' => 'required|string',
            'pic_name' => 'required|string',
            'rack_address' => 'sometimes|string',
            'usage' => 'required|enum',
            'location' => 'required|string',
        ]);

        $materials = Materials::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Material created successfully',
            'data' => $materials
        ], 201);
    }

    // PUT /api/materials/{id} (update existing material)
    public function update(Request $request, $id)
    {
        $materials = Materials::findOrFail($id);

        $validated = $request->validate([
            'description' => 'sometimes|string',
            'stock_minimum' => 'sometimes|integer|min:0',
            'stock_maximum' => 'sometimes|integer|min:0',
            'unit_of_measure' => 'sometimes|string',
            'pic_name' => 'sometimes|string',
            'rack_address' => 'sometimes|string',
            'location' => 'sometimes|string',
            'usage' => 'sometimes|enum'
        ]);

        $materials->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Material updated successfully',
            'data' => $materials
        ]);
    }

    // DELETE /api/materials/{id} (delete material)
    public function destroy($id)
    {
        Materials::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material deleted successfully'
        ]);
    }
}
