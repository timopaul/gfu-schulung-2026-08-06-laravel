<?php

declare(strict_types=1);

use App\Http\Controllers\OrderWebController;
use Illuminate\Support\Facades\Route;

/*
| Web-Erweiterung (Tag 1) – die Browser-Oberfläche zur Bestell-Domäne.
|
| Alle Routen laufen durch 'web.tenant': das Web-Pendant zur API-Middleware
| EnsureTenantAccess. Sie legt die tenant_id (aus der Session) in die
| Request-Attribute – danach ist der Web-Weg für Controller, FormRequest und
| OrderService nicht mehr vom API-Weg zu unterscheiden.
*/
Route::middleware('web.tenant')->group(function () {
    Route::get('/', fn () => redirect()->route('orders.index'));

    Route::get('orders', [OrderWebController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderWebController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderWebController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}/edit', [OrderWebController::class, 'edit'])->name('orders.edit');
    Route::put('orders/{order}', [OrderWebController::class, 'update'])->name('orders.update');

    Route::post('tenant/switch', [OrderWebController::class, 'switchTenant'])->name('tenant.switch');
});
