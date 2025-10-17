<?php

namespace Bardh78\LaravelSigma;

use Bardh78\LaravelSigma\Renderers\ErrorPageRenderer;
use Bardh78\LaravelSigma\Renderers\SigmaExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class LaravelSigmaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sigma.php' => config_path('sigma.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/sigma.php', 'sigma');

        // Register the exception renderer to hook into Laravel's error page
        $this->registerExceptionRenderer();
    }

    /**
     * Register the exception renderer to enhance error pages.
     */
    protected function registerExceptionRenderer(): void
    {
        // Only register in local/development environment
        if (!app()->environment('local', 'development')) {
            return;
        }

        // Register the error page renderer
        $this->app->singleton(ErrorPageRenderer::class);

        // Bind our custom exception renderer to Laravel's ExceptionRenderer contract
        $this->app->bind(
            'Illuminate\Contracts\Foundation\ExceptionRenderer',
            fn (Application $app) => $app->make(SigmaExceptionRenderer::class)
        );
    }
}
