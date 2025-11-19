<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse as AppLoginResponse;
use App\Http\Responses\RegisterResponse as AppRegisterResponse;
use App\Http\Responses\VerifyEmailResponse as AppVerifyEmailResponse;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Fortify's VerifyEmailResponse to our custom response
        $this->app->singleton(VerifyEmailResponseContract::class, AppVerifyEmailResponse::class);

        // Redirect unverified users to verification page after login/register
        $this->app->singleton(LoginResponseContract::class, AppLoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, AppRegisterResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
