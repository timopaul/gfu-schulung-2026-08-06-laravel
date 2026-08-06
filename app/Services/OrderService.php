<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Orders\CreateOrderAction;
use App\Actions\Orders\UpdateOrderAction;
use App\Data\CreateOrderData;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Application Service – der EINE gemeinsame Einstiegspunkt in die
 * Bestell-Domäne für ALLE Auslieferungswege.
 *
 * Warum das der Kern der Web-Übung ist:
 *   - Der API-Controller (OrderController) und der Web-Controller
 *     (OrderWebController) rufen ausschließlich diesen Service.
 *   - Keiner der beiden kennt Pipeline, Transaktion oder Events.
 *   - Dadurch verhält sich die Domäne über Browser und API garantiert
 *     identisch – und es feuert nachweislich DASSELBE Event
 *     (OrderCreated bzw. OrderUpdated), egal woher der Request kam.
 *
 * Der Service orchestriert die Use-Case-Actions (Block 2); die eigentliche
 * Transaktions- und Pipeline-Logik bleibt dort gekapselt.
 */
final class OrderService
{
    public function __construct(
        private readonly CreateOrderAction $createOrder,
        private readonly UpdateOrderAction $updateOrder,
    ) {
    }

    public function place(CreateOrderData $data): Order
    {
        return $this->createOrder->execute($data);
    }

    public function change(Order $order, CreateOrderData $data): Order
    {
        return $this->updateOrder->execute($order, $data);
    }

    /**
     * Liste aller Bestellungen eines Mandanten – lesbar dank Custom Builder
     * (Block 3) und ohne N+1 dank Eager Loading / Count-Subquery.
     *
     * @return LengthAwarePaginator<Order>
     */
    public function paginateForTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->forTenant($tenantId)
            ->withItemCount()
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Eine Bestellung im Mandantenkontext laden. Fremde Bestellungen führen
     * zu 404 – die Tenant-Isolation gilt für Web und API gleichermaßen.
     */
    public function findForTenant(int $tenantId, int $orderId): Order
    {
        return Order::query()
            ->forTenant($tenantId)
            ->with('items')
            ->findOrFail($orderId);
    }
}
