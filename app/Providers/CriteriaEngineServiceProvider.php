<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CriteriaEngine\CriteriaEngine;

class CriteriaEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CriteriaEngine::class, function ($app) {
            return new CriteriaEngine();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 