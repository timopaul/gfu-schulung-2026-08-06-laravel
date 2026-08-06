<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

/*
| Block 5 & 6 – Alle v1-Routen laufen durch die Tenant- und Logging-Middleware.
| Die Aliase 'tenant' und 'log.api' sind in bootstrap/app.php registriert.
*/
Route::prefix('v1')
    ->middleware(['tenant', 'log.api'])
    ->group(function () {
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::put('orders/{order}', [OrderController::class, 'update']);
    });
