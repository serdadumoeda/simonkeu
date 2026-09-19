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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL') || $this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Ensure storage and cache directories exist with write permissions to prevent tempnam() ErrorExceptions
        $storageDirs = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($storageDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        // Verify that configured view compiled directory exists and is writable
        $compiledPath = config('view.compiled', storage_path('framework/views'));
        if (!is_dir($compiledPath)) {
            @mkdir($compiledPath, 0775, true);
        }

        // If standard storage path is not writable (e.g. read-only host or permission restriction), fallback to /tmp
        if (!is_writable($compiledPath)) {
            $tmpCompiled = sys_get_temp_dir() . '/storage/framework/views';
            if (!is_dir($tmpCompiled)) {
                @mkdir($tmpCompiled, 0777, true);
            }
            config(['view.compiled' => $tmpCompiled]);
        }
    }
}
