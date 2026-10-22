<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Akun yang belum mengganti sandi awal hanya boleh membuka halaman ganti sandi.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return redirect()
                ->route('password.change')
                ->with('warning', trans('auth.must_change_password'));
        }

        return $next($request);
    }
}
