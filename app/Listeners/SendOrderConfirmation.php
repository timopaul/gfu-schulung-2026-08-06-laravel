<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Support\Facades\Log;

/**
 * Block 2 – Beispielhafter Listener. In echt würde hier eine Mail-Notification
 * verschickt; für die Schulung loggen wir nur, damit Event::fake() im Test greifbar wird.
 */
final class SendOrderConfirmation
{
    public function handle(OrderCreated $event): void
    {
        Log::info('Bestellbestätigung ausgelöst', [
            'order_id' => $event->order->id,
            'customer' => $event->order->customer_email,
        ]);
    }
}
