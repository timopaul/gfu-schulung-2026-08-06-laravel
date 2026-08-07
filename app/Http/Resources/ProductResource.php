<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ÜBUNG 7 (Tag 2) – API Resource.
 *
 * Naiv: parent::toArray() reicht ALLE DB-Spalten roh nach außen – inkl.
 * tenant_id (intern!) und Cent-Beträgen. Form das bewusst um.
 *
 * TODO:
 *   - price als Euro-Float (price_cents / 100) + 'currency' => 'EUR'
 *   - in_stock als Boolean ($this->stock > 0)
 *   - stock nur mit $this->when(...) wenn vorhanden
 *   - tenant_id & Timestamps weglassen
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // TODO: bewusst formen statt durchreichen
        return parent::toArray($request);
    }
}
