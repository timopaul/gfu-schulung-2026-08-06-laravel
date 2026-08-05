<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\CreateOrderAction;
use App\Data\CreateOrderData;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Block 2 – "Lean Controller": nimmt den validierten Request, übersetzt ihn in
 * ein DTO, delegiert an die Action und formt die Antwort. Keine Geschäftslogik.
 */
final class OrderController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        // Block 3: lesbare Query dank Custom Builder + Eager Loading gegen N+1.
        $orders = Order::query()
            ->forTenant($tenantId)
            ->withItemCount()
            ->with('items')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function store(CreateOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->execute(CreateOrderData::fromRequest($request));

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless(
            $order->tenant_id === (int) $request->attributes->get('tenant_id'),
            404,
        );

        return new OrderResource($order->load('items'));
    }
}
