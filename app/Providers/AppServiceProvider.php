<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
        // API responses return bare objects (no top-level "data" wrapper),
        // matching the original raw-model contract of the Posts endpoints.
        JsonResource::withoutWrapping();
    }
}
