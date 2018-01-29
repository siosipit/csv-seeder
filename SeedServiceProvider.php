<?php
namespace Seeder;

use Illuminate\Support\ServiceProvider;
use Seeder\SeedCommand;

class SeederServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register package services and commands.
     *
     * @return void
     */
    public function register()
    {
        $this->commands(SeedCommand::class);
    }
}
