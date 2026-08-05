<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block 5 – Terminable Middleware.
 *
 * handle() läuft VOR dem Controller, terminate() läuft NACHDEM die Antwort
 * an den Client gesendet wurde. Ideal für teures Logging/Metriken, ohne die
 * Antwortzeit für den Nutzer zu verlängern.
 */
final class LogApiUsage
{
    private float $startedAt = 0.0;

    public function handle(Request $request, Closure $next): Response
    {
        $this->startedAt = microtime(true);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $durationMs = (microtime(true) - $this->startedAt) * 1000;

        Log::info('API-Zugriff', [
            'tenant_id' => $request->attributes->get('tenant_id'),
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 2),
        ]);
    }
}
