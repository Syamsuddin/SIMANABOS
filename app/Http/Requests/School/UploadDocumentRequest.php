<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('report'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'file' => $this->fileRules(),
            'type_ids' => ['required', 'array', 'min:1'],
            'type_ids.*' => ['integer', 'distinct', Rule::exists('document_types', 'id')->where('scope', 'monthly')],
        ];
    }

    /** @return list<mixed> */
    protected function fileRules(): array
    {
        // mimetypes membaca isi berkas, bukan ekstensi (docs/21 §Berkas PDF).
        return [
            'required', 'file', 'mimetypes:application/pdf', 'max:'.config('simanabos.max_upload_kb'),
            // `min:` di Laravel dalam KB, jadi berkas 0 byte dicek terpisah.
            fn ($attribute, $value, $fail) => $value->getSize() > 0 ?: $fail('Berkas kosong tidak dapat diunggah.'),
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
        return [
            'file.mimetypes' => 'Berkas harus berupa PDF.',
            'file.max' => 'Ukuran berkas melebihi batas '.number_format(config('simanabos.max_upload_kb') / 1024, 0, ',', '.').' MB.',
            'type_ids.required' => 'Tandai minimal satu jenis dokumen.',
            'type_ids.min' => 'Tandai minimal satu jenis dokumen.',
        ];
    }
}
