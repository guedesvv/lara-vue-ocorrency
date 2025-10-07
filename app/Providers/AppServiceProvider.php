<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('approved', function ($attribute, $value, $parameters, $validator) {
            $user = User::where('email', $value)->first();

            if (! $user) {
                return false;
            }

            // Bloqueia se for exatamente "Nao"
            return $user->ApproveUser !== 'Nao';
        }, 'Seu acesso ainda não foi autorizado pelo administrador.');
    }
}
