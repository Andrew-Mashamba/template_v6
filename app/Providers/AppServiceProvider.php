<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Commands\GenerateBillCommand;
use App\Handlers\GenerateBillCommandHandler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindMethod(GenerateBillCommand::class.'@handle', function ($command, $app) {
            return $app->make(GenerateBillCommandHandler::class)->handle($command);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
