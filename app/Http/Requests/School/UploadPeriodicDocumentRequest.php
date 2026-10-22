<?php

namespace App\Http\Requests\School;

class UploadPeriodicDocumentRequest extends UploadDocumentRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('periodic'));
    }

    /** Jenis dokumen ditentukan oleh jenis laporan periodik, bukan dipilih pengguna. */
    public function rules(): array
    {
        return ['file' => $this->fileRules()];
    }
}
