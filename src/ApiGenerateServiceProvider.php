<?php

namespace Rodrixcornell\ApiGenerate;

use Illuminate\Support\ServiceProvider;

class ApiGenerateServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Rodrixcornell\ApiGenerate\ApiGenerate'
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}
