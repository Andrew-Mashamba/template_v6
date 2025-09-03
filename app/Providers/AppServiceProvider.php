<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Commands\GenerateBillCommand;
use App\Handlers\GenerateBillCommandHandler;
use App\Services\NbcPayments\GepgGatewayService;
use App\Services\NbcPayments\GepgLoggerService;
use App\Services\Payments\InternalFundsTransferService;
use App\Services\Payments\ExternalFundsTransferService;
use App\Services\Payments\MobileWalletTransferService;
use App\Services\Payments\BillPaymentService;

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

        // Register GEPG services with proper dependency injection
        $this->app->singleton(GepgLoggerService::class, function ($app) {
            return new GepgLoggerService();
        });

        $this->app->singleton(GepgGatewayService::class, function ($app) {
            return new GepgGatewayService($app->make(GepgLoggerService::class));
        });

        // Register new payment services
        $this->app->singleton(InternalFundsTransferService::class);
        $this->app->singleton(ExternalFundsTransferService::class);
        $this->app->singleton(MobileWalletTransferService::class);
        $this->app->singleton(BillPaymentService::class);
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
