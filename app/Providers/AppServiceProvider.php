<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\GuzzleHttp\ClientInterface::class, function () {
            return new \GuzzleHttp\Client([
                'base_uri' => config('openweather.base_uri'),
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
        });
    }
}
