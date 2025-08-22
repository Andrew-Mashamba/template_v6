<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $attempRequest = DB::table("password_policies")->where('id', 1)->first();
            $email = (string) $request->email;
            $limiter = $attempRequest ? $attempRequest->limiter : 5; // Default to 5 attempts if no policy exists

            return Limit::perMinute($limiter)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            $attempRequest = DB::table("password_policies")->where('id', 1)->first();
            $limiter = $attempRequest ? $attempRequest->limiter : 5; // Default to 5 attempts if no policy exists

            return Limit::perMinute($limiter)->by($request->session()->get('login.id'));
        });
    }

}
