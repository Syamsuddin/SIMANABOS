<?php

namespace App\Http\Requests\School;

class ReplaceDocumentRequest extends UploadDocumentRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('document'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'file' => $this->fileRules(),
            'replaced_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function reason(): ?string
    {
        return $this->filled('replaced_reason') ? trim((string) $this->input('replaced_reason')) : null;
    }
}
