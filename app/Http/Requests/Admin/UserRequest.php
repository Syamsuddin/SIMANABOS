<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target
            ? $this->user()->can('update', $target)
            : $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isNew = $this->route('user') === null;
        $role = RoleName::tryFrom((string) $this->input('role'));

        return [
            'username' => $isNew
                ? ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')]
                : ['prohibited'],
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', Rule::enum(RoleName::class)],
            // Role sekolah wajib punya sekolah; role dinas wajib tanpa sekolah (docs/05).
            'school_id' => $role?->isSchoolRole()
                ? ['required', 'integer', Rule::exists('schools', 'id')->whereNull('deleted_at')]
                : ['nullable', 'prohibited'],
            'password' => $isNew ? ['required', Password::min(10)->letters()->numbers()] : ['prohibited'],
            'is_active' => $isNew ? ['prohibited'] : ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_id.prohibited' => 'Akun peran dinas tidak boleh terikat ke sekolah.',
            'school_id.required' => 'Pilih sekolah untuk peran sekolah.',
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $target = $this->route('user');

            if ($target && $target->is($this->user()) && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Anda tidak dapat menonaktifkan akun sendiri.');
            }
        });
    }
}
