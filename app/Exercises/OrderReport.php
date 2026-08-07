<?php

declare(strict_types=1);

namespace App\Exercises;

use App\Models\Order;

/**
 * ÜBUNG 5 (Tag 2) – "Advanced Eloquent & Query-Optimierung".
 *
 * summarize() baut einen Paid-Umsatz-Report für einen Mandanten – aber teuer:
 *   1. Order::all() lädt ALLE Bestellungen aller Mandanten.
 *   2. Mandant + Status werden in PHP gefiltert statt in SQL.
 *   3. $order->items()->count() feuert pro Zeile eine eigene Query (N+1).
 *
 * Aufgabe:
 *   - Query über den OrderBuilder: ->forTenant($tenantId)->paid()
 *   - Item-Anzahl per Count-Subquery: ->withItemCount()  (=> $order->items_count)
 *   - Bonus: Model::preventLazyLoading() im AppServiceProvider aktivieren.
 *
 * Verifizieren: tinker + DB::enableQueryLog() – am Ende genau EINE Query.
 */
final class OrderReport
{
    /**
     * @return array<int, array{id: int, items: int, total_eur: float}>
     */
    public function summarize(int $tenantId): array
    {
        $orders = Order::all();

        $rows = [];

        foreach ($orders as $order) {
            if ($order->tenant_id !== $tenantId) {
                continue;
            }

            if ($order->status !== 'paid') {
                continue;
            }

            $rows[] = [
                'id' => $order->id,
                'items' => $order->items()->count(),
                'total_eur' => $order->total_cents / 100,
            ];
        }

        return $rows;
    }
}
