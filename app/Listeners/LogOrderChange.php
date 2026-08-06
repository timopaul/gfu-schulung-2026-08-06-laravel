<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderUpdated;
use Illuminate\Support\Facades\Log;

/**
 * Block 2 (Web-Erweiterung) – Listener für Bestelländerungen.
 *
 * Gleiches, quellen-neutrales Log-Format wie bei OrderCreated: Ob die Änderung
 * aus dem Web-Formular oder per PUT /api/v1/orders/{order} kam, ist im Log
 * nicht zu erkennen – weil beide Wege durch denselben OrderService laufen.
 */
final class LogOrderChange
{
    public function handle(OrderUpdated $event): void
    {
        Log::info('[Domain-Event] OrderUpdated', [
            'order_id' => $event->order->id,
            'tenant_id' => $event->order->tenant_id,
            'customer' => $event->order->customer_email,
            'total_eur' => $event->order->total_cents / 100,
        ]);
    }
}
