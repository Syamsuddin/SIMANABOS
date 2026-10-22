<?php

namespace App\Http\Requests\Admin;

use App\Models\FiscalYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FiscalYear::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2000,2100', Rule::unique('fiscal_years', 'year')],
            'default_deadline_day' => ['required', 'integer', 'between:1,28'],
        ];
    }
}
