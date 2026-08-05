<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block 5 – Tenant-Isolation: liest den API-Key aus dem Header, löst den
 * Mandanten auf und legt seine ID in die Request-Attribute. Ab hier arbeitet
 * die gesamte Anwendung nur im Kontext dieses Mandanten.
 */
final class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if ($apiKey === null) {
            return response()->json([
                'type' => 'https://httpstatuses.io/401',
                'title' => 'Unauthorized',
                'status' => 401,
                'detail' => 'Header X-Api-Key fehlt.',
            ], 401);
        }

        $tenant = Tenant::query()->where('api_key', $apiKey)->first();

        if ($tenant === null) {
            return response()->json([
                'type' => 'https://httpstatuses.io/403',
                'title' => 'Forbidden',
                'status' => 403,
                'detail' => 'Ungültiger API-Key.',
            ], 403);
        }

        // Mandantenkontext für Controller/DTO bereitstellen.
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
