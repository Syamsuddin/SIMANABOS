<?php

namespace App\Http\Requests;

use App\Enums\RecapPeriod;
use App\Enums\SchoolLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecapFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi di controller (Gate + bukan role sekolah)
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'tahun' => ['nullable', 'integer'],
            'periode' => ['nullable', Rule::enum(RecapPeriod::class)],
            'jenjang' => ['nullable', Rule::enum(SchoolLevel::class)],
            'kecamatan' => ['nullable', 'integer', 'exists:districts,id'],
            'sumber_dana' => ['nullable', 'integer', 'exists:fund_sources,id'],
        ];
    }
}
