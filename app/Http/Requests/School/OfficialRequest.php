<?php

namespace App\Http\Requests\School;

use App\Enums\OfficialRole;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai rute admin (`{school}`) dan rute sekolah (sekolah milik pengguna).
 */
class OfficialRequest extends FormRequest
{
    public function school(): ?School
    {
        return $this->route('school') ?? School::query()->find($this->user()->school_id);
    }

    public function authorize(): bool
    {
        $school = $this->school();

        return $school !== null && $this->user()->can('proposeOfficial', $school);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(OfficialRole::class)],
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'digits:18'],
            'started_at' => ['required', 'date'],
        ];
    }
}
