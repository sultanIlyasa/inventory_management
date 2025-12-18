<?php

namespace App\Http\Controllers;

use App\Exports\MaterialsExport;
use App\Services\Materials\MaterialBulkImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MaterialBulkController extends Controller
{
    public function export()
    {
        $fileName = 'materials-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new MaterialsExport(), $fileName);
    }

    public function import(Request $request, MaterialBulkImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $result = $service->handle($request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Bulk upload processed.',
            'data' => $result,
        ]);
    }
}
