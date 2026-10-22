<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CloseFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('fiscal_year'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'confirm_year' => ['nullable', 'string', 'max:4'],
        ];
    }
}
