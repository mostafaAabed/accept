<?php

namespace Mo\Accept;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AcceptServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {   
            $this->offerPublishing();
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }

    protected function offerPublishing()
    {
        if (! function_exists('config_path')) {
            // function not available and 'publish' not relevant in Lumen
            return;
        }
        
        if (! class_exists('CreateAcceptOrdersTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_accept_orders_table.php' => $this->getMigrationFileName('create_accept_orders_table.php'),
            ], 'migrations');
        }
        
        $this->publishes([
            __DIR__.'/../config/accept.php' => config_path('accept.php'),
        ], 'config');

    }

      /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @return string
     */
    protected function getMigrationFileName($migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationFileName) {
                return $filesystem->glob($path.'*_'.$migrationFileName);
            })
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}