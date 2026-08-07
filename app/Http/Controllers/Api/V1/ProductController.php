<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * ÜBUNG 7 (Tag 2) – Lean Controller für den Produkt-Endpoint.
 *
 * TODO: Produkte des aktuellen Mandanten laden
 *       ($request->attributes->get('tenant_id')) und als ProductResource
 *       ::collection(...) zurückgeben.
 */
final class ProductController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // TODO: Produkte des Mandanten laden
        return ProductResource::collection([]);
    }
}
