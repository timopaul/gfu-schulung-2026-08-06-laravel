<?php

declare(strict_types=1);

use App\Http\Controllers\ExerciseWebController;
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

/*
| Übungs-Runner (Tag 1, Block 1). BEWUSST ohne 'web.tenant': die DTO-Übung
| braucht weder Datenbank noch Mandant und läuft direkt nach dem Clonen.
| Die Seite prüft den Code per Reflection – refactoren, neu laden, grün werden.
*/
Route::get('uebung/1', [ExerciseWebController::class, 'dtoRefactoring'])->name('exercises.dto');
Route::get('uebung/2', [ExerciseWebController::class, 'serviceExtraction'])->name('exercises.service');
Route::get('uebung/3', [ExerciseWebController::class, 'actionExtraction'])->name('exercises.action');
Route::get('uebung/4', [ExerciseWebController::class, 'eventDispatch'])->name('exercises.event');
