<?php

namespace App\Http\Requests\Review;

use App\Models\ReviewNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('report'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(ReviewNote::TARGETS)],
            'target_key' => ['nullable', 'required_unless:target_type,general', 'string', 'max:50'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
