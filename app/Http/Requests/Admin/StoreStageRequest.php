<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('fiscal_year'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'stage_no' => [
                'required', 'integer', 'between:1,12',
                Rule::unique('fiscal_year_stages', 'stage_no')->where('fiscal_year_id', $this->route('fiscal_year')->id),
            ],
            'label' => ['required', 'string', 'max:50'],
            'start_month' => ['required', 'integer', 'between:1,12'],
            'end_month' => ['required', 'integer', 'between:1,12', 'gte:start_month'],
        ];
    }
}
