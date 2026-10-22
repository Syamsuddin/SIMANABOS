<?php

namespace App\Http\Requests\School;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = School::query()->find($this->user()->school_id);

        return $school !== null && $this->user()->can('updateProfile', $school);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
