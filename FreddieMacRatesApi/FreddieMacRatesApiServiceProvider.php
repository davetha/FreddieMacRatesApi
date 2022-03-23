<?php

namespace App\Services\FreddieMacRatesApi;

use Illuminate\Support\ServiceProvider;

class FreddieMacRatesApiServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FreddieMacRatesApi::class, fn ($app) => new FreddieMacRatesApi());
    }
}
