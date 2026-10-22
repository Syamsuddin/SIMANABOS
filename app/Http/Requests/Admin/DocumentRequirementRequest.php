<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportKind;
use App\Models\DocumentRequirement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [DocumentRequirement::class, $this->route('fiscal_year')]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fund_source_id' => $this->filled('fund_source_id') ? $this->input('fund_source_id') : null,
            'is_required' => $this->boolean('is_required'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')],
            'report_kind' => ['required', Rule::enum(ReportKind::class)],
            'school_status' => ['required', Rule::in(['negeri', 'swasta', 'all'])],
            'fund_source_id' => ['nullable', 'integer', Rule::exists('fund_sources', 'id')],
            'is_required' => ['boolean'],
        ];
    }

    /** Keunikan dijaga di sini karena UNIQUE MySQL mengizinkan banyak baris dengan fund_source_id NULL. */
    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $exists = $this->route('fiscal_year')->documentRequirements()
                ->where('document_type_id', $this->input('document_type_id'))
                ->where('report_kind', $this->input('report_kind'))
                ->where('school_status', $this->input('school_status'))
                ->when(
                    $this->input('fund_source_id') === null,
                    fn ($q) => $q->whereNull('fund_source_id'),
                    fn ($q) => $q->where('fund_source_id', $this->input('fund_source_id')),
                )->exists();

            if ($exists) {
                $validator->errors()->add('document_type_id', 'Aturan untuk kombinasi ini sudah ada.');
            }
        }];
    }
}
