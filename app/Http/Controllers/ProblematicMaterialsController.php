<?php

namespace App\Http\Controllers;

use App\Models\ProblematicMaterials;
use App\Services\ProblematicMaterialsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProblematicMaterialsController extends Controller
{
    public function __construct(
        protected ProblematicMaterialsService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page    = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);
        $status  = $request->query('status') ?: null; // SHORTAGE | CAUTION | null

        $paginator = $this->service->getProblematicMaterials($page, $perPage, $status);

        return response()->json([
            'success' => true,
            'data'    => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'estimated_gr' => ['nullable', 'date'],
        ]);

        $item = ProblematicMaterials::findOrFail($id);
        $item->update(['estimated_gr' => $validated['estimated_gr']]);

        return response()->json([
            'success'      => true,
            'estimated_gr' => $item->estimated_gr?->format('Y-m-d'),
        ]);
    }

    public function getConsumptionAverages(Request $request): JsonResponse
    {
        $data = $this->service->fetchConsumptionAveragesAll();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
