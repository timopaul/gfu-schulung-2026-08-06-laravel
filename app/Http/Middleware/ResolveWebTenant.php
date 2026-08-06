<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block 5 (Web-Pendant zu EnsureTenantAccess) – Mandantenauflösung für den Browser.
 *
 * Im Browser gibt es keinen X-Api-Key-Header. Stattdessen merkt sich die
 * Session den gewählten Mandanten; ein Umschalter im Layout ändert ihn.
 * Am Ende landet – exakt wie in der API-Middleware – die tenant_id in den
 * Request-Attributen. Dadurch funktionieren FormRequest und OrderService
 * ohne jede Sonderbehandlung für den Web-Weg.
 */
final class ResolveWebTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenants = Tenant::query()->orderBy('id')->get();

        abort_if($tenants->isEmpty(), 503, 'Keine Mandanten vorhanden – bitte `php artisan migrate --seed` ausführen.');

        $selectedId = (int) $request->session()->get('tenant_id', $tenants->first()->id);

        $tenant = $tenants->firstWhere('id', $selectedId) ?? $tenants->first();
        $request->session()->put('tenant_id', $tenant->id);

        // Gleiche Attribute wie EnsureTenantAccess – die Domäne merkt keinen Unterschied.
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant', $tenant);

        // Für den Mandanten-Umschalter und die Anzeige im Layout.
        View::share('currentTenant', $tenant);
        View::share('tenants', $tenants);

        return $next($request);
    }
}
