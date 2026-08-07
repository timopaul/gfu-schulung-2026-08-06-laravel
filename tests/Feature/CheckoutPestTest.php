<?php

declare(strict_types=1);

use App\Actions\Orders\CreateOrderAction;
use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use App\Events\OrderCreated;
use App\Exceptions\OutOfStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\postJson;

/*
| ÜBUNG 8 – Testen mit Pest.
|
| Die erste Vorlage unten ist fertig und grün. Deine Aufgabe: die fünf
| ->todo()-Einträge zu echten Tests ausbauen und am Ende die drei
| Architektur-Tests ergänzen. Alles läuft gegen die BASIS-Domäne
| (CreateOrderAction + /api/v1/orders) – keine Refactoring-Übung nötig.
|
| Feedback-Schleife:  ./vendor/bin/pest tests/Feature/CheckoutPestTest.php
| Nur die offenen Todos:  ./vendor/bin/pest --todos
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['api_key' => 'key_pest']);
    $this->headers = ['X-Api-Key' => 'key_pest'];
    $this->action = app(CreateOrderAction::class);
});

// ── Vorlage (bereits fertig) ────────────────────────────────────────────────
it('berechnet Zwischensumme, Steuer und Gesamt aus Menge × Preis', function () {
    Event::fake();

    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'price_cents' => 5000,
        'stock' => 10,
    ]);

    $order = $this->action->execute(new CreateOrderData(
        tenantId: $this->tenant->id,
        customerEmail: 'pest@example.test',
        items: [new OrderItemData($product->id, 2)],
    ));

    expect($order->subtotal_cents)->toBe(10000)
        ->and($order->tax_cents)->toBe(1900)
        ->and($order->total_cents)->toBe(11900);
});

// ── Todo-Checkliste – Schritt für Schritt zu echten Tests ausbauen ───────────
// Tipp: ->todo() aus der Zeile entfernen, ein Closure mit dem Test-Body ergänzen.

// Aufgabe 1: Bestellt jemand mehr, als auf Lager ist, muss execute() eine
//            OutOfStockException werfen UND die Transaktion zurückrollen
//            (keine Order, Bestand unverändert, kein OrderCreated).
//            Idiome: expect(fn () => ...)->toThrow(...), Event::assertNotDispatched().
it('wirft OutOfStockException und rollt die Transaktion zurück')->todo();

// Aufgabe 2: Dieselbe Rechenlogik für mehrere Warenkörbe prüfen – als DATASET
//            (->with([...])) statt drei kopierter Tests.
//            Datensätze z. B.: [5000,1,5000,950,5950], [5000,2,10000,1900,11900],
//            [2000,3,6000,1140,7140].
it('berechnet 19 % Steuer für verschiedene Warenkörbe (Dataset)')->todo();

// Aufgabe 3: Nach erfolgreicher Anlage muss OrderCreated GENAU EINMAL feuern.
//            Idiom: Event::fake([OrderCreated::class]) + Event::assertDispatchedTimes(...).
it('feuert OrderCreated genau einmal')->todo();

// Aufgabe 4: End-to-End über HTTP mit dem Laravel-Plugin: postJson auf
//            /api/v1/orders (mit X-Api-Key), Status 201 und die Euro-Beträge
//            aus der Resource prüfen (assertJsonPath 'data.amounts.subtotal' usw.).
it('legt per API eine Bestellung an und formt Euro-Beträge')->todo();

// Aufgabe 5: Ohne X-Api-Key muss die API mit 401 abweisen.
it('lehnt Anfragen ohne API-Key mit 401 ab')->todo();

// ── Aufgabe 6: Architektur-Tests (Pest 3) ───────────────────────────────────
// Ergänze drei arch()-Tests (kein Closure nötig, Pest prüft die Struktur):
//   - arch('...')->expect('App\\Data')->toBeReadonly();
//   - arch('...')->expect('App\\Actions')->toBeFinal();
//   - arch('...')->expect(['dd','dump','ray','var_dump'])->not->toBeUsed();
