<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\LogApiUsage;
use App\Http\Middleware\ResolveWebTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
    )
    // Event-Zuordnung passiert AUSSCHLIESSLICH explizit im EventServiceProvider
    // ($listen). Ohne dieses discover:false würde Laravel die Listener zusätzlich
    // automatisch in app/Listeners entdecken – jedes Event würde dann doppelt
    // behandelt (z. B. doppelte "[Domain-Event]"-Zeile im Log).
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        // Block 5 – benannte Middleware-Aliase für die Routen.
        $middleware->alias([
            'tenant' => EnsureTenantAccess::class,
            'log.api' => LogApiUsage::class,
            // Web-Pendant: löst den Mandanten aus der Session auf.
            'web.tenant' => ResolveWebTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Block 6 – globaler Handler: fachliche Fehler => RFC-7807 JSON.
        $exceptions->render(function (DomainException $e, Request $request): ?Response {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'type' => 'https://benefits.example/errors/'.class_basename($e),
                'title' => $e->title,
                'status' => $e->status,
                'detail' => $e->getMessage(),
            ], $e->status);
        });
    })
    ->create();
