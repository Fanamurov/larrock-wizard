<?php

namespace Larrock\ComponentWizard;

use Illuminate\Support\ServiceProvider;

class LarrockComponentWizardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'larrock');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'larrock');

        $this->publishes([
            __DIR__.'/lang' => resource_path('lang/larrock'),
            __DIR__.'/views' => base_path('resources/views/larrock'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app->make(WizardComponent::class);
    }
}
