<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OverdueDaysRequest extends FormRequest
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
            'search' => 'nullable|string|max:100',
            'pic' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:SHORTAGE,CAUTION,OVERFLOW,OK,UNCHECKED,N/A',
            'sortField' => 'nullable|string|in:status,days',
            'sortDirection' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:50',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'location.in' => 'Location must be either SUNTER_1 or SUNTER_2',
            'usage.in' => 'Usage must be DAILY, WEEKLY, or MONTHLY',
            'month.date_format' => 'Month must be in Y-m format (e.g., 2024-12)',
            'date.date_format' => 'Date must be in Y-m-d format (e.g., 2024-12-16)',
            'gentani.in' => 'Gentani must be either GENTAN-I or NON_GENTAN-I',
            'sortField.in' => 'Sort field must be either status or days',
            'sortDirection.in' => 'Sort direction must be ASC or DESC',
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
            'search' => $this->input('search'),
            'pic' => $this->input('pic'),
            'status' => $this->input('status'),
            'sortField' => $this->input('sortField', 'status'),
            'sortDirection' => $this->input('sortDirection', 'desc'),
        ], fn($value) => $value !== null && $value !== '');
    }

    public function getPaginationParams(): array
    {
        return [
            'per_page' => (int) $this->input('per_page', 15),
            'page' => (int) $this->input('page', 1),
        ];
    }
}
