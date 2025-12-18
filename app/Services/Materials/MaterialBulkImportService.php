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
                $action = $this->upsertMaterial($rowData);
                $result[$action === 'created' ? 'created' : 'updated']++;
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

        $required = [
            'material_id',
            'vendor_id',
            'vendor_number',
            'material_number',
            'description',
            'stock_minimum',
            'stock_maximum',
            'unit_of_measure',
            'rack_address',
            'usage',
            'location',
            'gentani',
            'pic_name',
        ];

        $missing = array_filter($required, fn($column) => !array_key_exists($column, $map));

        if ($missing) {
            throw ValidationException::withMessages([
                'file' => 'Missing required columns: ' . implode(', ', $missing),
            ]);
        }

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

    private function upsertMaterial(array $rowData): string
    {
        $materialId = $this->toInt($rowData['material_id']);
        $material = $materialId ? Materials::find($materialId) : null;

        if ($materialId && !$material) {
            throw ValidationException::withMessages([
                'material_id' => "Material ID {$materialId} was not found.",
            ]);
        }

        $vendorId = $this->resolveVendorId($rowData['vendor_id'], $rowData['vendor_number']);

        $payload = [
            'material_number' => $this->stringValue($rowData['material_number']),
            'description' => $this->stringValue($rowData['description']),
            'stock_minimum' => $this->toInt($rowData['stock_minimum']),
            'stock_maximum' => $this->toInt($rowData['stock_maximum']),
            'unit_of_measure' => $this->stringValue($rowData['unit_of_measure']),
            'rack_address' => $this->nullableString($rowData['rack_address']),
            'usage' => $this->normalizeEnum($rowData['usage'], $this->usageOptions, 'usage'),
            'location' => $this->normalizeEnum($rowData['location'], $this->locationOptions, 'location'),
            'gentani' => $this->stringValue($rowData['gentani']) ?? 'NON_GENTAN-I',
            'pic_name' => $this->stringValue($rowData['pic_name']),
            'vendor_id' => $vendorId,
        ];

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

        $validator->after(function ($validator) use ($payload, $material) {
            if (
                $payload['stock_minimum'] !== null &&
                $payload['stock_maximum'] !== null &&
                $payload['stock_minimum'] > $payload['stock_maximum']
            ) {
                $validator->errors()->add('stock_minimum', 'Stock minimum cannot be greater than stock maximum.');
            }

            $query = Materials::where('material_number', $payload['material_number']);
            if ($material) {
                $query->where('id', '!=', $material->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('material_number', 'Material number must be unique.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($material) {
            $material->update($payload);
            return 'updated';
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
