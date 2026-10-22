<?php

namespace App\Http\Requests\Admin;

use App\Models\District;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        $district = $this->route('district');

        return $district
            ? $this->user()->can('update', $district)
            : $this->user()->can('create', District::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('districts', 'code')->ignore($this->route('district'))],
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
