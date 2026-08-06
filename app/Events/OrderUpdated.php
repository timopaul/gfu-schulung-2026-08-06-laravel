<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Block 2 (Web-Erweiterung) – Gegenstück zu OrderCreated für Änderungen.
 *
 * Wichtig für die Schulung: Dieses Event wird von der Update-Logik im
 * OrderService gefeuert – egal ob die Änderung aus dem Browser-Formular
 * oder über PUT /api/v1/orders/{order} kommt. Ein Event, ein Nebeneffekt,
 * zwei Auslieferungswege.
 */
final class OrderUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }
}
