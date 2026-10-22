<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeadlinesRequest extends FormRequest
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
            'due_dates' => ['required', 'array', 'size:12'],
            'due_dates.*' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function attributes(): array
    {
        return array_combine(
            array_map(fn (int $m) => "due_dates.$m", range(1, 12)),
            array_map(fn (int $m) => "tenggat bulan ke-$m", range(1, 12)),
        );
    }
}
