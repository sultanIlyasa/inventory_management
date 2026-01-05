<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckComplianceRequest extends FormRequest
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
            'gentani' => 'nullable|string|in:GENTAN-I,NON_GENTAN-I',
            'search' => 'nullable|string|max:100',
            'pic' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:5|max:50',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'location.in' => 'Location must be either SUNTER_1 or SUNTER_2',
            'usage.in' => 'Usage must be DAILY, WEEKLY, or MONTHLY',
            'gentani.in' => 'Gentani must be either GENTAN-I or NON_GENTAN-I',
        ];
    }

    public function getFilters(): array
    {
        return array_filter([
            'usage' => $this->input('usage'),
            'location' => $this->input('location'),
            'gentani' => $this->input('gentani'),
            'search' => $this->input('search'),
            'pic' => $this->input('pic'),
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
