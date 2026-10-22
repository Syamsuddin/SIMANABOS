<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Cocokkan kredensial, tolak akun nonaktif, lalu buka sesi baru.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials($this->only('username'));

        if (! $user instanceof Authenticatable
            || ! $provider->validateCredentials($user, $this->only('password'))) {
            throw ValidationException::withMessages(['username' => trans('auth.failed')]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['username' => trans('auth.inactive')]);
        }

        Auth::login($user);
        $this->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
    }
}
