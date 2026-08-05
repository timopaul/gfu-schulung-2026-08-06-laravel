# Laravel für Fortgeschrittene – Praxis-Repository

Begleit-Repository zur GFU-Schulung **„Laravel PHP Framework für Fortgeschrittene"** (Kurs-ID `s5618`, GFU Cyrus AG, Köln).
Trainer: **Timo Paul** · Termin: **06.–07.08.2026**.

Dieses Projekt ist eine bewusst kleine, aber vollständige **E-Commerce-Order-Domäne**. Es dient als roter Faden für alle acht Blöcke: von typsicheren DTOs über Action-Klassen, Pipelines und Custom Middleware bis zu API-Resources und Tests.

> **Wichtig:** Das Repository enthält eine **lauffähige Referenzlösung**. In der Schulung arbeitest du die Übungen selbst heraus (live-coding), die fertigen Klassen dienen als Kontrolle. Wer neu einsteigt, forkt den `main`-Branch und arbeitet die Aufgaben unten der Reihe nach ab.

---

## Voraussetzungen

- PHP **8.2+** (`php -v`)
- Composer 2
- SQLite-Extension (für die In-Memory-Testdatenbank – bei den meisten PHP-Installationen dabei)
- Git

## Setup (einmalig, ~2 Minuten)

```bash
git clone <dein-fork> laravel-fortgeschrittene-praxis
cd laravel-fortgeschrittene-praxis

composer install
cp .env.example .env
php artisan key:generate

# Für eine persistente DB (optional – Tests laufen ohnehin In-Memory):
touch database/database.sqlite
php artisan migrate --seed
```

Tests ausführen:

```bash
php artisan test
# oder gezielt:
php artisan test --filter=OrderApiTest
```

Der lokale Dev-Server:

```bash
php artisan serve
```

## Die API in 30 Sekunden

Alle Routen liegen unter `/api/v1` und verlangen den Header `X-Api-Key`.
Der Seeder legt zwei Mandanten an: `key_acme_demo` und `key_globex_demo`.

```bash
# Bestellung anlegen
curl -X POST http://localhost:8000/api/v1/orders \
  -H "X-Api-Key: key_acme_demo" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
        "customer_email": "kunde@example.test",
        "voucher_code": "WELCOME10",
        "items": [{ "product_id": 1, "quantity": 2 }]
      }'
```

---

## Architektur-Überblick

```
app/
├── Data/                     DTOs (readonly) – typsichere Eingabe (Block 1)
│   ├── CreateOrderData.php
│   └── OrderItemData.php
├── Http/
│   ├── Requests/             Validierung, getrennt von der Domain (Block 1)
│   ├── Controllers/Api/V1/   Lean Controller (Block 2)
│   ├── Resources/            Output-Transformation (Block 6)
│   └── Middleware/           Tenant-Isolation & terminable Logging (Block 5)
├── Actions/Orders/           Ein Use-Case = eine Klasse (Block 2)
├── Pipelines/Checkout/       Sequentielle Geschäftslogik (Block 4)
├── Builders/                 Custom Eloquent Builder / Scopes (Block 3)
├── Events/ + Listeners/      Entkoppelte Nebeneffekte (Block 2)
├── Exceptions/               Fachliche Fehler => RFC 7807 (Block 6)
bootstrap/app.php             Middleware-Aliase + globaler Exception-Handler (Block 5/6)
```

Der Datenfluss einer Bestellung:

```
POST /api/v1/orders
  └─ EnsureTenantAccess (Middleware)      → setzt tenant_id
     └─ CreateOrderRequest                → validiert Rohdaten
        └─ CreateOrderData::fromRequest() → typsicheres DTO
           └─ CreateOrderAction::execute  → DB::transaction
              └─ Pipeline: CheckStock → ApplyDiscounts → CalculateTax
                 └─ Order + Items persistieren, Bestand -, OrderCreated dispatchen
                    └─ OrderResource                → RFC-konforme JSON-Antwort
```

---

## Die Übungen – Block für Block

Jede Übung nennt die relevanten Dateien. Empfehlung: pro Block einen eigenen Git-Branch (`git switch -c block-1`).

### Tag 1

**Block 1 – Typsichere DTOs.**
Ausgangslage: ein Controller, der mit `$request->all()` arbeitet. Ziel: `CreateOrderData::fromRequest($request)` mit `readonly`-Properties (PHP 8.2). Trenne Validierung (`CreateOrderRequest`) sauber von der Domain (`CreateOrderData`). Ergänze die Gutschein-Prüfung in `withValidator()`.
→ `app/Data/*`, `app/Http/Requests/CreateOrderRequest.php`

**Block 2 – Action Classes & Events.**
Verlagere die Logik aus `OrderController@store` in `CreateOrderAction`. Klammere alles in `DB::transaction()`. Feuere `OrderCreated` statt die Mail direkt zu senden. Der Controller bleibt „lean".
→ `app/Actions/Orders/CreateOrderAction.php`, `app/Events/OrderCreated.php`, `app/Listeners/SendOrderConfirmation.php`

**Block 3 – Advanced Eloquent.**
Aktiviere `Model::preventLazyLoading()` im `AppServiceProvider` und behebe die N+1-Fälle im `index()`. Baue den `OrderBuilder` mit lesbaren Scopes (`forTenant`, `paid`, `withItemCount`).
→ `app/Builders/OrderBuilder.php`, `app/Providers/AppServiceProvider.php`

**Block 4 – Pipeline-Pattern.**
Zerlege den Checkout in drei Pipes: `CheckStockPipe → ApplyDiscountsPipe → CalculateTaxPipe`. Nutze `Illuminate\Pipeline\Pipeline` in der Action. Der `CheckoutContext` transportiert die Zwischenergebnisse.
→ `app/Pipelines/Checkout/*`

### Tag 2

**Block 5 – Custom Middleware.**
Implementiere `EnsureTenantAccess` (Tenant-Isolation über `X-Api-Key`) und `LogApiUsage` als **terminable** Middleware (`terminate()` läuft nach dem Response). Registriere die Aliase in `bootstrap/app.php`.
→ `app/Http/Middleware/*`, `bootstrap/app.php`

**Block 6 – API Resources & Exception Handling.**
Baue `OrderResource` mit `whenLoaded()` (kein N+1, Cent→Euro-Transformation, Data Hiding). Registriere in `bootstrap/app.php` einen globalen Handler, der `DomainException` als **RFC-7807**-JSON rendert.
→ `app/Http/Resources/*`, `app/Exceptions/*`, `bootstrap/app.php`

**Block 7 – Testing.**
Schreibe End-to-End-Tests für `/api/v1/orders` und Unit-Tests für `CreateOrderAction`. Nutze `Event::fake()` zum Isolieren der Nebeneffekte und `RefreshDatabase` für die In-Memory-DB.
→ `tests/Feature/Api/OrderApiTest.php`, `tests/Unit/CreateOrderActionTest.php`

**Block 8 – Code Audit & Q&A.**
Gemeinsamer Pull-Request-Review im Plenum. Bring dein eigenes Firmenbeispiel mit – wir mappen die Muster auf deinen Code.

---

## Lernziele

Nach den zwei Tagen strukturierst du Laravel jenseits einfacher CRUD-Apps: **wartbar, testbar, typsicher und performant** – so, wie es in großen Enterprise-Codebasen tragfähig ist.

## Lizenz

MIT – zur freien Verwendung in Schulung und eigenen Projekten.
