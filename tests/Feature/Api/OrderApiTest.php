<?php

declare(strict_types=1);

use App\Events\OrderCreated;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Voucher;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/*
| Block 7 – End-to-End-Tests für /api/v1/orders.
| Sie prüfen den vollständigen Durchlauf: Middleware -> Request -> Action ->
| Pipeline -> Resource. Facade Fakes isolieren Nebeneffekte.
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['api_key' => 'key_test']);
    $this->headers = ['X-Api-Key' => 'key_test'];
});

it('lehnt Anfragen ohne API-Key ab', function () {
    postJson('/api/v1/orders', [])->assertStatus(401);
});

it('legt eine Bestellung an und berechnet Summen korrekt', function () {
    Event::fake([OrderCreated::class]);

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 10000,
        'stock' => 10,
    ]);

    $payload = [
        'customer_email' => 'kunde@example.test',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ];

    $response = postJson('/api/v1/orders', $payload, $this->headers);

    $response->assertStatus(201)
        ->assertJsonPath('data.amounts.subtotal', 200)   // 2 x 100,00 €
        ->assertJsonPath('data.amounts.tax', 38)         // 19 %
        ->assertJsonPath('data.amounts.total', 238);

    // Nebeneffekt wurde ausgelöst, aber (dank fake) nicht ausgeführt.
    Event::assertDispatched(OrderCreated::class);

    // Bestand wurde in derselben Transaktion reduziert.
    expect($product->fresh()->stock)->toBe(8);
});

it('wendet einen gültigen Gutschein an', function () {
    Event::fake();

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 10000,
        'stock' => 10,
    ]);

    Voucher::factory()->create(['code' => 'SAVE10', 'percent_off' => 10]);

    $response = postJson('/api/v1/orders', [
        'customer_email' => 'kunde@example.test',
        'voucher_code' => 'SAVE10',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $this->headers);

    // Rechnung: 100 € − 10 € Rabatt = 90 € steuerbar; 19 % = 17,10 €; Summe 107,10 €.
    $response->assertStatus(201)
        ->assertJsonPath('data.amounts.discount', 10)
        ->assertJsonPath('data.amounts.tax', 17.1)
        ->assertJsonPath('data.amounts.total', 107.1);
});

it('gibt einen RFC-7807-Fehler bei fehlendem Bestand zurück', function () {
    Event::fake();

    $product = Product::factory()->outOfStock()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 5000,
    ]);

    $response = postJson('/api/v1/orders', [
        'customer_email' => 'kunde@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], $this->headers);

    $response->assertStatus(409)
        ->assertJsonPath('title', 'Out Of Stock')
        ->assertJsonStructure(['type', 'title', 'status', 'detail']);
});

it('isoliert Bestellungen pro Mandant', function () {
    $other = Tenant::factory()->create(['api_key' => 'key_other']);
    $product = Product::factory()->create(['tenant_id' => $other->id, 'stock' => 5]);

    // Bestellung im fremden Mandanten anlegen ...
    postJson('/api/v1/orders', [
        'customer_email' => 'fremd@example.test',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], ['X-Api-Key' => 'key_other'])->assertStatus(201);

    // ... darf im eigenen Mandanten nicht auftauchen.
    getJson('/api/v1/orders', $this->headers)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
