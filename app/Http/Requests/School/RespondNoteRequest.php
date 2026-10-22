<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

class RespondNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('respond', $this->route('report'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['response_body' => ['required', 'string', 'max:2000']];
    }
}
