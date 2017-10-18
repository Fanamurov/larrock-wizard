<?php

namespace Larrock\ComponentWizard;

use Illuminate\Support\ServiceProvider;
use Larrock\ComponentWizard\Commands\WizardImportClearCommand;
use Larrock\ComponentWizard\Commands\WizardImportCommand;
use Larrock\ComponentWizard\Commands\WizardImportSheetCommand;

class LarrockComponentWizardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'larrock');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'larrock');

        $this->publishes([
            __DIR__.'/lang' => resource_path('lang/vendor/larrock'),
            __DIR__.'/views' => base_path('resources/views/vendor/larrock'),
            __DIR__.'/config/larrock-wizard.php' => config_path('larrock-wizard.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make(WizardComponent::class);

        $this->app->bind('command.wizard:import', WizardImportCommand::class);
        $this->app->bind('command.wizard:sheet', WizardImportSheetCommand::class);
        $this->app->bind('command.wizard:clear', WizardImportClearCommand::class);
        $this->commands([
            'command.wizard:import',
            'command.wizard:sheet',
            'command.wizard:clear',
        ]);

        $this->mergeConfigFrom( __DIR__.'/config/larrock-wizard.php', 'larrock-wizard');
    }
}
