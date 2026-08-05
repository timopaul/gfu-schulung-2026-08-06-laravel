<?php

declare(strict_types=1);

use App\Actions\Orders\CreateOrderAction;
use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use App\Events\OrderCreated;
use App\Exceptions\OutOfStockException;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;

/*
| Block 7 – Unit-nahe Tests der Action ohne HTTP-Schicht.
| Wir rufen die Action direkt auf und prüfen ihr Verhalten und ihre Nebeneffekte.
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->action = app(CreateOrderAction::class);
});

it('persistiert Order + Items und berechnet Steuer', function () {
    Event::fake();

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 5000,
        'stock' => 4,
    ]);

    $data = new CreateOrderData(
        tenantId: $this->tenant->id,
        customerEmail: 'unit@example.test',
        items: [new OrderItemData($product->id, 2)],
    );

    $order = $this->action->execute($data);

    expect($order->subtotal_cents)->toBe(10000)
        ->and($order->tax_cents)->toBe(1900)
        ->and($order->total_cents)->toBe(11900)
        ->and($order->items)->toHaveCount(1);

    expect($product->fresh()->stock)->toBe(2);
    Event::assertDispatched(OrderCreated::class);
});

it('wirft OutOfStockException und rollt die Transaktion zurück', function () {
    Event::fake();

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 5000,
        'stock' => 1,
    ]);

    $data = new CreateOrderData(
        tenantId: $this->tenant->id,
        customerEmail: 'unit@example.test',
        items: [new OrderItemData($product->id, 3)],
    );

    expect(fn () => $this->action->execute($data))
        ->toThrow(OutOfStockException::class);

    // Rollback: keine Order angelegt, Bestand unverändert.
    expect(App\Models\Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(1);
    Event::assertNotDispatched(OrderCreated::class);
});
