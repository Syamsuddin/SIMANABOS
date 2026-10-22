<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('document'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type_ids' => ['required', 'array', 'min:1'],
            'type_ids.*' => ['integer', 'distinct', Rule::exists('document_types', 'id')->where('scope', 'monthly')],
        ];
    }

    /** @return list<int> */
    public function typeIds(): array
    {
        return array_map('intval', $this->validated('type_ids'));
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['type_ids.required' => 'Tandai minimal satu jenis dokumen.', 'type_ids.min' => 'Tandai minimal satu jenis dokumen.'];
    }
}
