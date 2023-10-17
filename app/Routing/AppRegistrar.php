<?php
namespace App\Routing;

use Exception;
use Illuminate\Http\Request;
use App\Contracts\RouteRegistrar;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use Illuminate\Contracts\Routing\Registrar;


class AppRegistrar implements RouteRegistrar
{
    public function map(Registrar $registrar):void
    {
        Route::middleware('web')->group(function () {
            Route::get('/', [HomeController::class, 'index'])->name('home'); 
        });

        Route::get('/amo/token', function (Request $request) {
            Log::debug($request);
        }); 

    }
}