<?php

namespace App\Http\Requests\Admin;

use App\Enums\SchoolLevel;
use App\Enums\SchoolStatus;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school
            ? $this->user()->can('update', $school)
            : $this->user()->can('create', School::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'npsn' => ['required', 'digits:8', Rule::unique('schools', 'npsn')->ignore($this->route('school'))],
            'name' => ['required', 'string', 'max:150'],
            'level' => ['required', Rule::enum(SchoolLevel::class)],
            'status' => ['required', Rule::enum(SchoolStatus::class)],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
