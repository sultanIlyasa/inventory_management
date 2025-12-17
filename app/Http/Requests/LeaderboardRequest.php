<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation logic separated from controller
 * Single Responsibility: Validate leaderboard requests
 */
class LeaderboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'location' => 'nullable|string|in:SUNTER_1,SUNTER_2',
            'usage' => 'nullable|string|in:DAILY,WEEKLY,MONTHLY',
            'month' => 'nullable|date_format:Y-m',
            'date' => 'nullable|date_format:Y-m-d',
            'gentani' => 'nullable|string|in:GENTAN-I,NON_GENTAN-I',
            'per_page' => 'nullable|integer|min:5|max:100',
            'page' => 'nullable|integer|min:1',
            'tab' => 'nullable|string|in:CAUTION,SHORTAGE',
            'pic_name'=>'nullable'
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'location.in' => 'Location must be either SUNTER_1 or SUNTER_2',
            'usage.in' => 'Usage must be DAILY, WEEKLY, or MONTHLY',
            'month.date_format' => 'Month must be in Y-m format (e.g., 2024-12)',
            'date.date_format' => 'Date must be in Y-m-d format (e.g., 2024-12-16)',
            'gentani.in' => 'Gentani must be either GENTAN-I or NON_GENTAN-I',
            'per_page.min' => 'Per page must be at least 5',
            'per_page.max' => 'Per page cannot exceed 100',
        ];
    }

    /**
     * Get sanitized filters (business logic helper)
     */
    public function getFilters(): array
    {
        return array_filter([
            'date' => $this->input('date'),
            'month' => $this->input('month'),
            'usage' => $this->input('usage'),
            'location' => $this->input('location'),
            'gentani' => $this->input('gentani'),
            'pic_name' => $this->input('pic_name')
        ], fn($value) => $value !== null);
    }

    /**
     * Get pagination parameters
     */
    public function getPaginationParams(): array
    {
        return [
            'per_page' => (int) $this->input('per_page', 10),
            'page' => (int) $this->input('page', 1),
        ];
    }
}
