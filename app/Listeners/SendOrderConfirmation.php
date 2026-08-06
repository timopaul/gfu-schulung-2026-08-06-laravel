<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Support\Facades\Log;

/**
 * Block 2 – Beispielhafter Listener. In echt würde hier eine Mail-Notification
 * verschickt; für die Schulung loggen wir nur, damit Event::fake() im Test greifbar wird.
 *
 * Das Log-Format ist bewusst QUELLEN-NEUTRAL: Es steht nirgends, ob die
 * Bestellung im Browser oder über die API angelegt wurde. Genau das macht im
 * Log sichtbar, dass Web und API durch dieselbe Domäne laufen. Beobachten mit:
 *   tail -f storage/logs/laravel.log | grep Domain-Event
 */
final class SendOrderConfirmation
{
    public function handle(OrderCreated $event): void
    {
        Log::info('[Domain-Event] OrderCreated', [
            'order_id' => $event->order->id,
            'tenant_id' => $event->order->tenant_id,
            'customer' => $event->order->customer_email,
            'total_eur' => $event->order->total_cents / 100,
        ]);
    }
}
