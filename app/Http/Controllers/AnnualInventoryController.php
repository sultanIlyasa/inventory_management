<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AnnualInventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnnualInventoryController extends Controller
{
    protected AnnualInventoryService $service;

    public function __construct(AnnualInventoryService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/annual-inventory
     * Get all PIDs with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'location' => $request->query('location'),
            'status' => $request->query('status'),
        ];

        $perPage = $request->query('per_page', 20);

        $data = $this->service->getAllPIDs($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $data['items'],
            'pagination' => $data['pagination'],
        ]);
    }

    /**
     * GET /api/annual-inventory/search
     * Search PIDs by PID number or PIC name
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->query('q', '');
        $filters = [
            'location' => $request->query('location'),
            'status' => $request->query('status'),
        ];

        $perPage = $request->query('per_page', 20);

        $data = $this->service->searchByPID($search, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $data['items'],
            'pagination' => $data['pagination'],
        ]);
    }

    /**
     * GET /api/annual-inventory/{id}
     * Get single PID with all items
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->service->getPIDWithItems($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'PID not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/annual-inventory/pid/{pid}
     * Get PID by exact PID number
     */
    public function showByPID(string $pid): JsonResponse
    {
        $data = $this->service->getByPID($pid);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'PID not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * PUT /api/annual-inventory/items/{itemId}
     * Update single item (for counting)
     */
    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'actual_qty' => 'nullable|numeric|min:0',
            'soh' => 'nullable|numeric',
            'outstanding_gr' => 'nullable|numeric',
            'outstanding_gi' => 'nullable|numeric',
            'error_movement' => 'nullable|numeric',
            'counted_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:PENDING,COUNTED,VERIFIED',
        ]);

        $imageFile = $request->hasFile('image') ? $request->file('image') : null;

        $result = $this->service->insertActualQty($itemId, $validated, $imageFile);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $result['data'],
        ]);
    }

    /**
     * POST /api/annual-inventory/items/bulk-update
     * Bulk update multiple items
     */
    public function bulkUpdateItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:annual_inventory_items,id',
            'items.*.actual_qty' => 'nullable|numeric|min:0',
            'items.*.soh' => 'nullable|numeric',
            'items.*.outstanding_gr' => 'nullable|numeric',
            'items.*.outstanding_gi' => 'nullable|numeric',
            'items.*.error_movement' => 'nullable|numeric',
            'items.*.counted_by' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string',
            'items.*.status' => 'nullable|string|in:PENDING,COUNTED,VERIFIED',
        ]);

        $result = $this->service->bulkUpdateItems($validated['items']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$result['updated']} items",
            'updated' => $result['updated'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * POST /api/annual-inventory/import
     * Import PIDs from Excel file
     */
    public function importPID(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $result = $this->service->importExcel($path);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Import completed',
            'pids_created' => $result['pids_created'],
            'pids_updated' => $result['pids_updated'],
            'items_created' => $result['items_created'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * POST /api/annual-inventory/items/{itemId}/upload-image
     * Upload image for an item
     */
    public function uploadImage(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        $result = $this->service->uploadItemImage($itemId, $request->file('image'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'image_path' => $result['image_path'],
            'image_url' => $result['image_url'],
        ]);
    }

    /**
     * GET /api/annual-inventory/template
     * Download PID import Excel template
     */
    // public function pidTemplate()
    // {
    //     return $this->service->generatePIDTemplate();
    // }

    /**
     * GET /api/annual-inventory/statistics
     * Get dashboard statistics
     */
    public function statistics(): JsonResponse
    {
        $data = $this->service->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/annual-inventory/locations
     * Get unique locations for filter dropdown
     */
    public function locations(): JsonResponse
    {
        $locations = $this->service->getLocations();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    /**
     * PUT /api/annual-inventory/{id}
     * Update PID details
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'pid' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'status' => 'nullable|string|in:Not Checked,In Progress,Completed',
        ]);

        $result = $this->service->updatePID($id, $validated);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'PID updated successfully',
            'data' => $result['data'],
        ]);
    }

    /**
     * DELETE /api/annual-inventory/{id}
     * Delete a PID and all its items
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deletePID($id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * GET /api/annual-inventory/export
     * Export PIDs with actual quantities to Excel
     */
    public function export(Request $request)
    {
        // Handle pids parameter - can be comma-separated string or array
        $pids = $request->query('pids');
        if (is_string($pids) && !empty($pids)) {
            $pids = array_map('trim', explode(',', $pids));
        }

        $filters = [
            'search' => $request->query('search'),
            'location' => $request->query('location'),
            'status' => $request->query('status'),
            'pids' => $pids,
            'mode' => $request->query('mode', 'auto'), // auto | single | zip
            'pid' => $request->query('pid'), // specific single PID to export
        ];

        try {
            \Log::info('Export: Request received', $filters);

            $result = $this->service->exportToExcel($filters);

            if ($result === null) {
                \Log::warning('Export: No data found');
                return response()->json([
                    'success' => false,
                    'message' => 'No data found for export.',
                ], 404);
            }

            \Log::info('Export: Returning response');
            return $result;
        } catch (\Throwable $e) {
            \Log::error('Export: Failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/annual-inventory/by-pid/{pid}
     * Get PID with items using PID number (with pagination)
     */
    public function showByPIDWithPagination(Request $request, string $pid): JsonResponse
    {
        $perPage = $request->query('per_page', 50);
        $page = $request->query('page', 1);
        $search = $request->query('search', '');

        $data = $this->service->getByPIDWithPagination($pid, $perPage, $page, $search);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'PID not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/annual-inventory/discrepancy
     * Get items for discrepancy analysis
     */
    public function discrepancy(Request $request): JsonResponse
    {
        $filters = [
            'pid_id' => $request->query('pid_id'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'discrepancy_type' => $request->query('discrepancy_type'),
            'location' => $request->query('location'),
            'counted_only' => $request->query('counted_only', false),
        ];

        $perPage = $request->query('per_page', 50);

        $data = $this->service->getDiscrepancyItems($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/annual-inventory/pids-dropdown
     * Get all PIDs for dropdown filter
     */
    public function pidsDropdown(): JsonResponse
    {
        $pids = $this->service->getAllPIDsForDropdown();

        return response()->json([
            'success' => true,
            'data' => $pids,
        ]);
    }

    /**
     * GET /api/annual-inventory/discrepancy/template
     * Download Excel template for discrepancy import
     */
    public function discrepancyTemplate()
    {
        return $this->service->generateDiscrepancyTemplate();
    }

    /**
     * POST /api/annual-inventory/discrepancy/import
     * Import discrepancy data from Excel
     */
    public function discrepancyImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->service->importDiscrepancyExcel($file->getRealPath());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Import completed',
            'updated' => $result['updated'],
            'errors' => $result['errors'] ?? [],
        ]);
    }

    /**
     * GET /api/annual-inventory/discrepancy/export
     * Export discrepancy data to Excel with filters
     */
    public function discrepancyExport(Request $request)
    {
        $filters = [
            'pid_id' => $request->query('pid_id'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'discrepancy_type' => $request->query('discrepancy_type'),
            'location' => $request->query('location'),
            'counted_only' => true, // Only export counted items
        ];

        return $this->service->exportDiscrepancyToExcel($filters);
    }

    /**
     * POST /api/annual-inventory/discrepancy/bulk-update
     * Bulk update discrepancy fields (SOH, outstanding GR/GI, error movement)
     */
    public function bulkUpdateDiscrepancy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:annual_inventory_items,id',
            'items.*.soh' => 'nullable|numeric',
            'items.*.outstanding_gr' => 'nullable|numeric',
            'items.*.outstanding_gi' => 'nullable|numeric',
            'items.*.error_movement' => 'nullable|numeric',
        ]);

        $result = $this->service->bulkUpdateDiscrepancy($validated['items']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$result['updated']} items",
            'updated' => $result['updated'],
            'errors' => $result['errors'] ?? [],
        ]);
    }
}
