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
        return ['require_principal_approval' => ['required', 'boolean']];
    }
}
