<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class UnlockReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('unlock', $this->route('report'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
