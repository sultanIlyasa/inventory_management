<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecoveryDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location' => 'nullable|string|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|string|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m',
            'date' => 'nullable|date_format:Y-m-d',
            'gentani' => 'nullable|string|in:GENTAN-I,NON_GENTAN-I',
            'per_page' => 'nullable|integer|min:5|max:50',
            'page' => 'nullable|integer|min:1',
            'year' => 'nullable|integer|min:2000|max:2100',
        ];
    }

    public function getFilters(): array
    {
        return array_filter([
            'date' => $this->input('date'),
            'month' => $this->input('month'),
            'usage' => $this->input('usage'),
            'location' => $this->input('location'),
            'gentani' => $this->input('gentani'),
        ], fn($value) => $value !== null && $value !== '');
    }

    public function getPaginationParams(): array
    {
        return [
            'per_page' => (int) $this->input('per_page', 10),
            'page' => (int) $this->input('page', 1),
        ];
    }

    public function getTrendYear(): int
    {
        if ($this->filled('year')) {
            return (int) $this->input('year');
        }

        if ($this->filled('month')) {
            return (int) substr($this->input('month'), 0, 4);
        }

        return (int) now()->format('Y');
    }
}
