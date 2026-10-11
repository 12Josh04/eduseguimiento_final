<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Vista de paginación propia (íconos Font Awesome, acorde al diseño del sistema).
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        // Directivas @auth/@guest basadas en sesión propia (no Breeze/Eloquent)
        Blade::directive('auth', function () {
            return "<?php if(session('user_id')): ?>";
        });
        Blade::directive('endauth', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('guest', function () {
            return "<?php if(!session('user_id')): ?>";
        });
        Blade::directive('endguest', function () {
            return "<?php endif; ?>";
        });
    }
}
