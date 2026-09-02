<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL') || $this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');

            static $migrated = false;
            if (!$migrated) {
                $migrated = true;
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $e) {
                    // Ignore migration errors if already up-to-date
                }
            }
        }
    }
}
