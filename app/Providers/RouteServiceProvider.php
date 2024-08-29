<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Este espacio de nombres es aplicado a las rutas del controlador de tu aplicación.
     * Además, se establece como la ruta raíz de la URL.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Definir las rutas de tu aplicación.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Definir las rutas "web" de la aplicación.
     *
     * Estas rutas reciben middleware de sesión, CSRF, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Definir las rutas "api" para la aplicación.
     *
     * Estas rutas son típicamente stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
