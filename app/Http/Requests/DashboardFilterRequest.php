<?php

namespace App\Http\Requests;

use App\Enums\ReportStatus;
use App\Enums\SchoolLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardFilterRequest extends FormRequest
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
            'dari' => ['nullable', 'integer', 'between:1,12'],
            'sampai' => ['nullable', 'integer', 'between:1,12', 'gte:dari'],
            'jenjang' => ['nullable', Rule::enum(SchoolLevel::class)],
            'status' => ['nullable', Rule::enum(ReportStatus::class)],
            'kecamatan' => ['nullable', 'integer', 'exists:districts,id'],
            'sumber_dana' => ['nullable', 'integer', 'exists:fund_sources,id'],
        ];
    }
}
