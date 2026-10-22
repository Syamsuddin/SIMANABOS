<?php

namespace App\Http\Requests;

use App\Enums\AttentionTrigger;
use App\Enums\SchoolLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttentionFilterRequest extends FormRequest
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
            'pemicu' => ['nullable', Rule::enum(AttentionTrigger::class)],
            'jenjang' => ['nullable', Rule::enum(SchoolLevel::class)],
            'kecamatan' => ['nullable', 'integer', 'exists:districts,id'],
        ];
    }
}
