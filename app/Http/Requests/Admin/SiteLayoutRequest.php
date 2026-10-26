<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['header', 'footer', 'megamenu'])],
            'status' => ['required', 'string', Rule::in(['draft', 'publish'])],
            'display_conditions' => ['nullable', 'array'],
            'display_conditions.all_pages' => ['boolean'],
            'display_conditions.include_pages' => ['nullable', 'array'],
            'display_conditions.include_pages.*' => ['integer'],
            'display_conditions.exclude_pages' => ['nullable', 'array'],
            'display_conditions.exclude_pages.*' => ['integer'],
            'display_conditions.include_routes' => ['nullable', 'array'],
            'display_conditions.include_routes.*' => ['string'],
            'display_conditions.exclude_routes' => ['nullable', 'array'],
            'display_conditions.exclude_routes.*' => ['string'],
            'builder_data' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('display_conditions');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['display_conditions' => $decoded]);
            }
        }
    }
}
