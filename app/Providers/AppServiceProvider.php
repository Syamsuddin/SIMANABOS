<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('username')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(
                fn (Request $request, array $headers) => redirect()
                    ->route('login')
                    ->withInput($request->only('username'))
                    ->withErrors(['username' => trans('auth.throttle', ['seconds' => $headers['Retry-After']])])
            );
        });
    }
}
