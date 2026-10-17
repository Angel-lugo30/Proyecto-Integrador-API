
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación pública
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');
});

/*
|--------------------------------------------------------------------------
| Rutas autenticadas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin,user,technician'])
    ->group(function () {

        // Cuenta y sesión.
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | Equipos
        |--------------------------------------------------------------------------
        */

        Route::get('/equipment', [EquipmentController::class, 'index']);
        Route::get('/equipment/{equipment}', [EquipmentController::class, 'show'])
            ->whereNumber('equipment');
        

        Route::middleware('role:admin')->group(function () {
            Route::post('/equipment', [EquipmentController::class, 'store']);
            Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])
                ->whereNumber('equipment');
            Route::patch('/equipment/{equipment}', [EquipmentController::class, 'update'])
                ->whereNumber('equipment');
        });

        /*
        |--------------------------------------------------------------------------
        | Tickets
        |--------------------------------------------------------------------------
        */

        Route::get('/tickets', [TicketController::class, 'index']);

        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
            ->whereNumber('ticket');

        Route::middleware('role:admin,user')->group(function () {
            Route::post('/tickets', [TicketController::class, 'store']);
        });

        Route::middleware('role:admin,technician')->group(function () {
            Route::patch(
                '/tickets/{ticket}/status',
                [TicketController::class, 'changeStatus']
            )->whereNumber('ticket');
        });

        Route::middleware('role:admin')->group(function () {
            Route::patch(
                '/tickets/{ticket}/assignment',
                [TicketController::class, 'assign']
            )->whereNumber('ticket');
        });
    });
