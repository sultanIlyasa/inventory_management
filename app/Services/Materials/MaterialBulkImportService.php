<?php

namespace App\Services\Materials;

use App\Models\Materials;
use App\Models\Vendors;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MaterialBulkImportService
{
    private array $usageOptions = ['DAILY', 'WEEKLY', 'MONTHLY'];
    private array $locationOptions = ['SUNTER_1', 'SUNTER_2'];

    public function handle(UploadedFile $file): array
    {
        $rows = $this->readWorksheet($file);

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file must contain at least one data row.',
            ]);
        }

        $headerMap = $this->buildHeaderMap(array_shift($rows));

        $result = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rowData = $this->mapRowData($row, $headerMap);

            if ($this->isRowEmpty($rowData)) {
                continue;
            }

            try {
                // Check if this is a delete action
                $action = strtolower(trim($rowData['action'] ?? ''));

                if ($action === 'delete') {
                    $this->deleteMaterial($rowData);
                    $result['deleted']++;
                } else {
                    $actionResult = $this->upsertMaterial($rowData);
                    $result[$actionResult === 'created' ? 'created' : 'updated']++;
                }
            } catch (ValidationException $exception) {
                $result['skipped']++;
                $result['errors'][] = "Row {$rowNumber}: " . implode(' ', $exception->validator->errors()->all());
            } catch (\Throwable $throwable) {
                $result['skipped']++;
                $result['errors'][] = "Row {$rowNumber}: {$throwable->getMessage()}";
            }
        }

        return $result;
    }

    private function readWorksheet(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
        } catch (\Throwable $throwable) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read the uploaded file: ' . $throwable->getMessage(),
            ]);
        }

        return $spreadsheet->getActiveSheet()->toArray(null, false, false, false);
    }

    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim((string) $header));
            if ($normalized !== '') {
                $map[$normalized] = $index;
            }
        }

        // No required columns - material_id is optional (auto-generated for new records)
        // All fields are optional for partial updates
        // At minimum, the file should have some data columns

        return $map;
    }

    private function mapRowData(array $row, array $map): array
    {
        $data = [];
        foreach ($map as $column => $index) {
            $value = $row[$index] ?? null;
            $data[$column] = is_string($value) ? trim($value) : $value;
        }

        return $data;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a cell value is truly empty (null, empty string, or whitespace only)
     */
    private function isEmptyCell($value): bool
    {
        return $value === null || $value === '' || trim((string) $value) === '';
    }

    private function deleteMaterial(array $rowData): void
    {
        $materialId = $this->toInt($rowData['material_id']);

        if (!$materialId) {
            throw ValidationException::withMessages([
                'material_id' => 'Material ID is required for delete operation.',
            ]);
        }

        $material = Materials::find($materialId);

        if (!$material) {
            throw ValidationException::withMessages([
                'material_id' => "Material ID {$materialId} was not found.",
            ]);
        }

        $material->delete();
    }

    private function upsertMaterial(array $rowData): string
    {
        // Allow empty material_id for creating new materials
        $materialId = $this->toInt($rowData['material_id'] ?? null);
        $material = $materialId ? Materials::find($materialId) : null;

        // Only throw error if material_id is provided but not found
        if ($materialId && !$material) {
            throw ValidationException::withMessages([
                'material_id' => "Material ID {$materialId} was not found.",
            ]);
        }

        // Build payload with only provided (non-empty) fields
        $payload = [];

        // Handle vendor resolution if vendor fields are provided
        if (!$this->isEmptyCell($rowData['vendor_id'] ?? null) || !$this->isEmptyCell($rowData['vendor_number'] ?? null)) {
            $vendorId = $this->resolveVendorId($rowData['vendor_id'] ?? null, $rowData['vendor_number'] ?? null);
            if ($vendorId !== null) {
                $payload['vendor_id'] = $vendorId;
            }
        }

        // Add fields only if they are not empty
        if (!$this->isEmptyCell($rowData['material_number'] ?? null)) {
            $payload['material_number'] = $this->stringValue($rowData['material_number']);
        }
        if (!$this->isEmptyCell($rowData['description'] ?? null)) {
            $payload['description'] = $this->stringValue($rowData['description']);
        }
        if (!$this->isEmptyCell($rowData['stock_minimum'] ?? null)) {
            $payload['stock_minimum'] = $this->toInt($rowData['stock_minimum']);
        }
        if (!$this->isEmptyCell($rowData['stock_maximum'] ?? null)) {
            $payload['stock_maximum'] = $this->toInt($rowData['stock_maximum']);
        }
        if (!$this->isEmptyCell($rowData['unit_of_measure'] ?? null)) {
            $payload['unit_of_measure'] = $this->stringValue($rowData['unit_of_measure']);
        }
        if (!$this->isEmptyCell($rowData['rack_address'] ?? null)) {
            $payload['rack_address'] = $this->nullableString($rowData['rack_address']);
        }
        if (!$this->isEmptyCell($rowData['usage'] ?? null)) {
            $payload['usage'] = $this->normalizeEnum($rowData['usage'], $this->usageOptions, 'usage');
        }
        if (!$this->isEmptyCell($rowData['location'] ?? null)) {
            $payload['location'] = $this->normalizeEnum($rowData['location'], $this->locationOptions, 'location');
        }
        if (!$this->isEmptyCell($rowData['gentani'] ?? null)) {
            $payload['gentani'] = $this->stringValue($rowData['gentani']);
        }
        if (!$this->isEmptyCell($rowData['pic_name'] ?? null)) {
            $payload['pic_name'] = $this->stringValue($rowData['pic_name']);
        }

        // If updating existing material, only validate provided fields
        if ($material) {
            // Validate only the fields being updated
            $rules = [];
            if (isset($payload['material_number'])) $rules['material_number'] = 'required|string';
            if (isset($payload['description'])) $rules['description'] = 'required|string';
            if (isset($payload['stock_minimum'])) $rules['stock_minimum'] = 'required|integer|min:0';
            if (isset($payload['stock_maximum'])) $rules['stock_maximum'] = 'required|integer|min:0';
            if (isset($payload['unit_of_measure'])) $rules['unit_of_measure'] = 'required|string';
            if (isset($payload['rack_address'])) $rules['rack_address'] = 'nullable|string';
            if (isset($payload['usage'])) $rules['usage'] = 'required|in:' . implode(',', $this->usageOptions);
            if (isset($payload['location'])) $rules['location'] = 'required|in:' . implode(',', $this->locationOptions);
            if (isset($payload['gentani'])) $rules['gentani'] = 'required|string';
            if (isset($payload['pic_name'])) $rules['pic_name'] = 'required|string';
            if (isset($payload['vendor_id'])) $rules['vendor_id'] = 'nullable|integer|exists:vendors,id';

            $validator = Validator::make($payload, $rules);

            $validator->after(function ($validator) use ($payload, $material) {
                // Validate stock minimum/maximum if both are being updated or if one is being updated
                $newStockMin = $payload['stock_minimum'] ?? $material->stock_minimum;
                $newStockMax = $payload['stock_maximum'] ?? $material->stock_maximum;

                if ($newStockMin !== null && $newStockMax !== null && $newStockMin > $newStockMax) {
                    $validator->errors()->add('stock_minimum', 'Stock minimum cannot be greater than stock maximum.');
                }

                // Validate material_number + location uniqueness if being updated
                if (isset($payload['material_number']) || isset($payload['location'])) {
                    $newMaterialNumber = $payload['material_number'] ?? $material->material_number;
                    $newLocation = $payload['location'] ?? $material->location;

                    $query = Materials::where('material_number', $newMaterialNumber)
                        ->where('location', $newLocation)
                        ->where('id', '!=', $material->id);

                    if ($query->exists()) {
                        $validator->errors()->add('material_number', 'Material number must be unique within the same location.');
                    }
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $material->update($payload);
            return 'updated';
        }

        // Creating new material - all required fields must be provided
        $requiredForCreate = [
            'material_number', 'description', 'stock_minimum', 'stock_maximum',
            'unit_of_measure', 'usage', 'location', 'pic_name'
        ];

        foreach ($requiredForCreate as $field) {
            if (!isset($payload[$field]) || $payload[$field] === null) {
                throw ValidationException::withMessages([
                    $field => "The {$field} field is required when creating a new material.",
                ]);
            }
        }

        // Set default for gentani if not provided
        if (!isset($payload['gentani'])) {
            $payload['gentani'] = 'NON_GENTAN-I';
        }

        $validator = Validator::make($payload, [
            'material_number' => 'required|string',
            'description' => 'required|string',
            'stock_minimum' => 'required|integer|min:0',
            'stock_maximum' => 'required|integer|min:0',
            'unit_of_measure' => 'required|string',
            'rack_address' => 'nullable|string',
            'usage' => 'required|in:' . implode(',', $this->usageOptions),
            'location' => 'required|in:' . implode(',', $this->locationOptions),
            'gentani' => 'required|string',
            'pic_name' => 'required|string',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
        ]);

        $validator->after(function ($validator) use ($payload) {
            if (
                $payload['stock_minimum'] !== null &&
                $payload['stock_maximum'] !== null &&
                $payload['stock_minimum'] > $payload['stock_maximum']
            ) {
                $validator->errors()->add('stock_minimum', 'Stock minimum cannot be greater than stock maximum.');
            }

            // Validate material_number + location combination uniqueness
            if (Materials::where('material_number', $payload['material_number'])
                ->where('location', $payload['location'])
                ->exists()) {
                $validator->errors()->add('material_number', 'Material number must be unique within the same location.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        Materials::create($payload);
        return 'created';
    }

    private function resolveVendorId($vendorIdValue, $vendorNumberValue): ?int
    {
        $vendorId = $this->toInt($vendorIdValue);

        if ($vendorId) {
            $exists = Vendors::whereKey($vendorId)->exists();
            if (!$exists) {
                throw ValidationException::withMessages([
                    'vendor_id' => "Vendor ID {$vendorId} was not found.",
                ]);
            }

            return $vendorId;
        }

        $vendorNumber = $this->stringValue($vendorNumberValue);
        if ($vendorNumber) {
            $vendor = Vendors::where('vendor_number', $vendorNumber)->first();

            if (!$vendor) {
                throw ValidationException::withMessages([
                    'vendor_number' => "Vendor number {$vendorNumber} was not found.",
                ]);
            }

            return $vendor->id;
        }

        return null;
    }

    private function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function stringValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableString($value): ?string
    {
        $string = $this->stringValue($value);

        return $string === null ? null : $string;
    }

    private function normalizeEnum($value, array $allowed, string $column): ?string
    {
        $stringValue = $this->stringValue($value);

        if ($stringValue === null) {
            return null;
        }

        $upper = strtoupper($stringValue);

        if (!in_array($upper, $allowed, true)) {
            throw ValidationException::withMessages([
                $column => "Invalid {$column} value: {$stringValue}. Allowed values: " . implode(', ', $allowed),
            ]);
        }

        return $upper;
    }
}
