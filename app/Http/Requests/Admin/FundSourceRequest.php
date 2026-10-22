<?php

namespace App\Http\Requests\Admin;

use App\Models\FundSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FundSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fundSource = $this->route('fund_source');

        return $fundSource
            ? $this->user()->can('update', $fundSource)
            : $this->user()->can('create', FundSource::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * Kode sumber dana tidak diubah setelah dibuat; hanya nama dan status aktif.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];

        if (! $this->route('fund_source')) {
            $rules['code'] = ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_]+$/', Rule::unique('fund_sources', 'code')];
        }

        return $rules;
    }
}
