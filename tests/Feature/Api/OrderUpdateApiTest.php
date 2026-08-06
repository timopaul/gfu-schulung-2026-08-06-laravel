<?php

declare(strict_types=1);

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/*
| Web-Erweiterung – Beweis, dass der PUT-Weg dieselbe Domäne durchläuft:
| Beim Ändern einer Bestellung feuert OrderUpdated, Bestände werden korrekt
| zurück- und neu gebucht. Genau dieses Event sehen die Trainees auch im Log,
| wenn sie im Browser speichern – Web und API sind nicht zu unterscheiden.
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['api_key' => 'key_test']);
    $this->headers = ['X-Api-Key' => 'key_test'];
});

it('ändert eine Bestellung, feuert OrderUpdated und bucht Bestand um', function () {
    Event::fake([OrderCreated::class, OrderUpdated::class]);

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 10000,
        'stock' => 10,
    ]);

    // Anlegen: 2 Stück -> Bestand 8.
    $created = postJson('/api/v1/orders', [
        'customer_email' => 'kunde@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ], $this->headers)->assertStatus(201);

    expect($product->fresh()->stock)->toBe(8);
    $orderId = $created->json('data.id');

    // Ändern: jetzt 3 Stück. Alte 2 werden zurückgebucht (10), dann 3 abgezogen (7).
    putJson("/api/v1/orders/{$orderId}", [
        'customer_email' => 'kunde@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
    ], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.amounts.subtotal', 300)
        ->assertJsonPath('data.amounts.total', 357); // 300 + 19 %

    Event::assertDispatched(OrderUpdated::class);
    expect($product->fresh()->stock)->toBe(7);
});

it('verhindert das Ändern einer fremden Bestellung (404)', function () {
    Event::fake();

    $other = Tenant::factory()->create(['api_key' => 'key_other']);
    $product = Product::factory()->create(['tenant_id' => $other->id, 'stock' => 5]);

    $foreign = postJson('/api/v1/orders', [
        'customer_email' => 'fremd@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], ['X-Api-Key' => 'key_other'])->assertStatus(201);

    $foreignId = $foreign->json('data.id');

    // Eigener Mandant darf die fremde Bestellung nicht ändern.
    putJson("/api/v1/orders/{$foreignId}", [
        'customer_email' => 'dieb@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $this->headers)->assertStatus(404);
});
