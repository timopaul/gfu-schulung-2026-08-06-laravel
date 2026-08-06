<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\CreateOrderData;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Block 2 – "Lean Controller": nimmt den validierten Request, übersetzt ihn in
 * ein DTO und delegiert an den OrderService. Keine Geschäftslogik hier.
 *
 * Web-Erweiterung: Genau dieselbe OrderService-Methode ruft auch der
 * OrderWebController auf – dadurch feuern beide Wege dasselbe Domain-Event.
 */
final class OrderController
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $this->orders->paginateForTenant(
            (int) $request->attributes->get('tenant_id'),
        );

        return OrderResource::collection($orders);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place(CreateOrderData::fromRequest($request));

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->assertBelongsToTenant($request, $order);

        return new OrderResource($order->load('items'));
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $this->assertBelongsToTenant($request, $order);

        $updated = $this->orders->change($order, CreateOrderData::fromRequest($request));

        return new OrderResource($updated->load('items'));
    }

    /**
     * Tenant-Isolation (Block 5): eine Bestellung aus einem fremden Mandanten
     * existiert aus Sicht des aktuellen Keys schlicht nicht → 404.
     */
    private function assertBelongsToTenant(Request $request, Order $order): void
    {
        abort_unless(
            $order->tenant_id === (int) $request->attributes->get('tenant_id'),
            404,
        );
    }
}
