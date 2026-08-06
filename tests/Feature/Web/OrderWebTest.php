<?php

declare(strict_types=1);

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

/*
| Web-Erweiterung – Smoke-Test der Browser-Oberfläche.
| Kern der Schulungsaussage: Der Web-Weg feuert exakt dasselbe Domain-Event
| wie die API, weil beide durch OrderService -> Action laufen.
*/

beforeEach(function () {
    // Ohne Session-tenant_id nimmt ResolveWebTenant den ersten Mandanten.
    $this->tenant = Tenant::factory()->create();
});

it('zeigt die Bestellliste an', function () {
    get(route('orders.index'))
        ->assertOk()
        ->assertSee('Bestellungen');
});

it('legt eine Bestellung über das Formular an und feuert OrderCreated', function () {
    Event::fake([OrderCreated::class]);

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 10000,
        'stock' => 10,
    ]);

    post(route('orders.store'), [
        'customer_email' => 'web@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertRedirect(route('orders.index'))
        ->assertSessionHas('status');

    Event::assertDispatched(OrderCreated::class);

    expect(Order::where('customer_email', 'web@example.test')->exists())->toBeTrue();
    expect($product->fresh()->stock)->toBe(8);
});

it('ändert eine Bestellung über das Formular und feuert OrderUpdated', function () {
    Event::fake([OrderCreated::class, OrderUpdated::class]);

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 10000,
        'stock' => 10,
    ]);

    post(route('orders.store'), [
        'customer_email' => 'web@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $order = Order::where('customer_email', 'web@example.test')->firstOrFail();

    put(route('orders.update', $order), [
        'customer_email' => 'web@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 4]],
    ])->assertRedirect(route('orders.index'))
        ->assertSessionHas('status');

    Event::assertDispatched(OrderUpdated::class);
    expect($product->fresh()->stock)->toBe(6); // 10 - 4
});

it('spielt fachliche Fehler (kein Bestand) als Formularfehler zurück', function () {
    Event::fake();

    $product = Product::factory()->outOfStock()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 5000,
    ]);

    post(route('orders.store'), [
        'customer_email' => 'web@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertSessionHasErrors('domain');
});
