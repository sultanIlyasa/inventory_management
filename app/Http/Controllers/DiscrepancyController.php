<?php

namespace App\Http\Controllers;

use App\Services\DiscrepancyService;
use App\Exports\DiscrepancyTemplateExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DiscrepancyController extends Controller
{
    protected DiscrepancyService $service;

    public function __construct(DiscrepancyService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the discrepancy dashboard page
     */
    public function index()
    {
        $locations = $this->service->getLocations();
        $pics = $this->service->getPics();
        $usages = $this->service->getUsages();

        return Inertia::render('Discrepancy/index', [
            'locations' => $locations,
            'pics' => $pics,
            'usages' => $usages,
        ]);
    }

    /**
     * Get all discrepancy data via API
     */
    public function getDiscrepancyData(Request $request)
    {
        $filters = [
            'location' => $request->query('location'),
            'pic' => $request->query('pic'),
            'usage' => $request->query('usage'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by'),
            'sort_order' => $request->query('sort_order', 'asc'),
        ];

        $perPage = $request->query('per_page', 50);

        $data = $this->service->getDiscrepancyData($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Download Excel template for import
     */
    public function downloadTemplate()
    {
        $fileName = 'discrepancy_import_template_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new DiscrepancyTemplateExport(), $fileName);
    }

    /**
     * Import external system data from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $result = $this->service->importFromExcel($file->getRealPath());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Excel imported successfully',
                'data' => [
                    'imported' => $result['imported'],
                    'updated' => $result['updated'],
                    'errors' => $result['errors'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Update discrepancy data for a specific material
     */
    public function update(Request $request, int $materialId)
    {
        $request->validate([
            'price' => 'nullable|numeric|min:0',
            'soh' => 'nullable|integer',
            'outstanding_gr' => 'nullable|integer',
            'outstanding_gi' => 'nullable|integer',
            'error_moving' => 'nullable|integer',
        ]);

        $result = $this->service->updateDiscrepancy($materialId, $request->all());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Discrepancy updated successfully',
                'data' => $result['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Sync discrepancy records with daily inputs
     */
    public function sync()
    {
        $result = $this->service->syncWithDailyInputs();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Sync completed successfully',
                'data' => [
                    'created' => $result['created'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }
}
