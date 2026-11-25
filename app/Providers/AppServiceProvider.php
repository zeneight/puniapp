<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

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
        $this->configureDefaults();

        // Log aktivitas login dan logout
        Event::listen(Login::class, function ($event) {
        activity('Autentikasi')
            ->causedBy($event->user)
            ->event('login')
            ->withProperties([
                'ip_address' => request()->ip(),
                'browser'    => request()->userAgent(),
            ])
            ->log('Login ke dalam sistem');
        });

        Event::listen(Logout::class, function ($event) {
            if ($event->user) {
                activity('Autentikasi')
                    ->causedBy($event->user)
                    ->event('logout')
                    ->withProperties([
                        'ip_address' => request()->ip(),
                    ])
                    ->log('Logout dari sistem');
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
