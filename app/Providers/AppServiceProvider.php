<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
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
        $this->passTempDirectoryToLocalServer();
    }

    /**
     * On Windows `php artisan serve` drops TEMP/TMP from the server's environment, so PHP
     * cannot store uploaded files ("unable to create a temporary file") and every upload fails.
     */
    private function passTempDirectoryToLocalServer(): void
    {
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables = array_values(array_unique([...ServeCommand::$passthroughVariables, 'TEMP', 'TMP']));
        }
    }
}
