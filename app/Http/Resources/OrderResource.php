<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Block 6 – Entkoppelt die DB-Struktur von der API-Repräsentation.
 * Cent-Beträge werden nach außen als Euro-Float dargestellt (Data Hiding
 * & bewusste Transformation). Items nur, wenn sie geladen wurden.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'customer_email' => $this->customer_email,
            'amounts' => [
                'subtotal' => $this->subtotal_cents / 100,
                'discount' => $this->discount_cents / 100,
                'tax' => $this->tax_cents / 100,
                'total' => $this->total_cents / 100,
                'currency' => 'EUR',
            ],
            // whenLoaded verhindert unbeabsichtigte N+1-Queries in der Resource.
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
