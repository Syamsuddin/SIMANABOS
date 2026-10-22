<?php

namespace App\Http\Requests\Admin;

use App\Models\AppSetting;
use Illuminate\Foundation\Http\FormRequest;

class AppSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', AppSetting::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'require_principal_approval' => ['required', 'boolean'],
            'default_deadline_day' => ['required', 'integer', 'between:1,28'],
            'reminder_days_before' => ['required', 'integer', 'between:1,14'],
        ];
    }
}
