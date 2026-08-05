<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Block 2 – Nebeneffekte (Mail, Rechnung, Webhook) werden NICHT im
 * Controller/der Action erledigt, sondern über dieses Event entkoppelt.
 */
final class OrderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }
}
