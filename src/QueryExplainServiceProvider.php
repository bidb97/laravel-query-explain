<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain;

use Illuminate\Support\ServiceProvider;
use PhpParser\ParserFactory;
use PhpParser\Parser;

/**
 * QueryExplain Service Provider
 *
 * Registers and boots the QueryExplain package services.
 * Handles configuration publishing and route registration.
 * Sets up the AST parser singleton for static analysis of PHP code.
 */
class QueryExplainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Merges the package configuration with the application configuration.
     * This allows the user to override package settings in their own config files.
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
     * Sets up the AST parser singleton, publishes configuration files,
     * loads package routes and views. This method is called after all
     * other service providers have been registered.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register the PHP parser as a singleton to reuse the same instance
        $this->app->singleton(Parser::class, function () {
            return (new ParserFactory())->createForNewestSupportedVersion();
        });

        // Publish configuration file to allow user customization
        $this->publishes([
            __DIR__.'/../config/query-explain.php' => config_path('query-explain.php'),
        ], 'query-explain');

        // Load the package routes for the query explain interface
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load the package views with the 'query-explain' namespace
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'query-explain');
    }
}
