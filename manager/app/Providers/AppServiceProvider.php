<?php

namespace App\Providers;

use App\Services\DockerService;
use App\Services\EvolutionService;
use App\Services\PhpConfigService;
use App\Services\WordPressService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DockerService::class);
        $this->app->singleton(EvolutionService::class);
        $this->app->singleton(PhpConfigService::class);
        $this->app->singleton(WordPressService::class);
    }

    public function boot(): void
    {
        //
    }
}
