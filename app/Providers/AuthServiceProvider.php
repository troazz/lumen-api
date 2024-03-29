<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
    }

    /**
     * Boot the authentication services for the application.
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $email = $request->header('PHP_AUTH_USER');
            $password = $request->header('PHP_AUTH_PW');
            if ($email && $password) {
                $user = User::where('email', $email)->first();
                if ($user && Hash::check($password, $user->password)) {
                    return $user;
                }
            }
        });
    }
}
