<?php

namespace Bidb97\QueryExplain;

use Illuminate\Support\ServiceProvider;

/**
 * QueryExplain Service Provider
 *
 * Registers and boots the QueryExplain package services.
 * Handles configuration publishing and route registration.
 */
class QueryExplainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/query-explain.php',
            'query-explain'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/query-explain.php' => config_path('query-explain.php'),
        ], 'query-explain');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load views if needed
       $this->loadViewsFrom(__DIR__.'/../resources/views', 'query-explain');
    }
}
