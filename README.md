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

## Fork mit dem Basis-Repository aktualisieren

Während der Schulung kommen neue Übungen und Fixes ins Basis-Repository (`upstream`). So holst du sie in deinen Fork, ohne deine eigene Arbeit zu verlieren.

**Einmalig:** das Basis-Repository als zweites Remote namens `upstream` eintragen (dein eigener Fork bleibt `origin`).

```bash
git remote add upstream https://github.com/timopaul/gfu-schulung-2026-08-06-laravel.git
git remote -v   # Kontrolle: origin = dein Fork, upstream = Basis
```

**Bei jedem Update:** neue Commits holen und in deinen aktuellen Branch übernehmen.

```bash
git fetch upstream
git checkout main
git merge upstream/main        # bringt die neuen Übungen in deinen main
```

Arbeitest du auf einem eigenen Übungs-Branch (empfohlen, z. B. `block-1`), rebasest du ihn danach auf den frischen `main`:

```bash
git checkout block-1
git rebase main
```

Deinen aktualisierten Fork auf GitHub schieben:

```bash
git push origin main
```

> Merge-Konflikt? Kein Drama: Git markiert die betroffenen Stellen mit `<<<<<<<` / `>>>>>>>`. Behalte deine Lösung oder die aus `upstream`, entferne die Marker, dann `git add <datei>` und `git merge --continue`. Im Zweifel kurz melden – wir schauen gemeinsam drauf.

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

## Web-Oberfläche (Tag 1)

Damit am **ersten Tag sofort etwas im Browser sichtbar** ist – und die eigene Arbeit direkt getestet werden kann – gibt es zur API ein schlankes Backoffice: eine **Bestell-Liste** plus **Formular zum Anlegen und Bearbeiten**.

```bash
# einmalig eine Datei-DB anlegen und seeden (die Web-UI braucht eine persistente DB):
touch database/database.sqlite
php artisan migrate:fresh --seed

php artisan serve
# -> http://localhost:8000/orders
```

| Route | Beschreibung |
|-------|--------------|
| `GET  /orders` | Liste der Bestellungen des aktuellen Mandanten |
| `GET  /orders/create` | Formular „Neue Bestellung" |
| `POST /orders` | Bestellung anlegen |
| `GET  /orders/{order}/edit` | Formular „Bestellung bearbeiten" |
| `PUT  /orders/{order}` | Bestellung ändern |
| `POST /tenant/switch` | Mandant wechseln (Dropdown oben rechts) |

**Mandant im Browser:** Die API identifiziert den Mandanten über den Header `X-Api-Key`; der Browser hat keinen solchen Header. Deshalb löst die Middleware `ResolveWebTenant` den Mandanten aus der **Session** auf (Umschalter oben rechts). Ab dann ist der Web-Weg für Controller, FormRequest und `OrderService` **nicht mehr vom API-Weg zu unterscheiden**.

### Der „Aha"-Moment: gleiches Event, egal ob Web oder API

Web-Controller und API-Controller sind bewusst **symmetrisch**: je *ein* schlanker Controller, der die Arbeit an den gemeinsamen `OrderService` delegiert. Das Domain-Event feuert damit an **einer** Stelle – unabhängig von der Herkunft der Anfrage. Zum Beweis ein Terminal mitlaufen lassen:

```bash
tail -f storage/logs/laravel.log | grep Domain-Event
```

Jetzt einmal im Browser eine Bestellung anlegen **und** einmal per `curl` (siehe oben). In beiden Fällen erscheint dieselbe Zeile:

```
[Domain-Event] OrderCreated {"order_id":1,"tenant_id":1,"customer":"…","total_eur":238}
```

Beim Bearbeiten analog `OrderUpdated`. Genau das ist die Kernaussage: **die Oberfläche ist nur Darstellung – die Domäne (und ihr Event) ist die eine Wahrheit.**

---

## Architektur-Überblick

```
app/
├── Data/                     DTOs (readonly) – typsichere Eingabe (Block 1)
│   ├── CreateOrderData.php
│   └── OrderItemData.php
├── Http/
│   ├── Requests/             Validierung, getrennt von der Domain (Block 1)
│   ├── Controllers/
│   │   ├── Api/V1/           Lean Controller – JSON (Block 2)
│   │   └── OrderWebController Lean Controller – Blade/Redirect (Web-Erweiterung)
│   ├── Resources/            Output-Transformation (Block 6)
│   └── Middleware/           Tenant-Isolation (API: X-Api-Key, Web: Session)
├── Services/OrderService     Gemeinsamer Einstieg für Web + API
├── Actions/Orders/           Ein Use-Case = eine Klasse (Block 2)
├── Pipelines/Checkout/       Sequentielle Geschäftslogik (Block 4)
├── Builders/                 Custom Eloquent Builder / Scopes (Block 3)
├── Events/ + Listeners/      Entkoppelte Nebeneffekte (Block 2)
├── Exceptions/               Fachliche Fehler => RFC 7807 (Block 6)
resources/views/orders/       Liste + Formular (ohne Build-Step, Inline-CSS)
routes/web.php                Browser-Routen (Web-Erweiterung)
bootstrap/app.php             Middleware-Aliase, Events-Discovery aus, Exception-Handler
```

Web und API teilen sich Requests, DTO und `OrderService` – der einzige Unterschied ist die Darstellung:

```
POST /orders (Browser)          POST /api/v1/orders (API)
        │                                │
   OrderWebController            Api\V1\OrderController
        └──────────────┬─────────────────┘
                 OrderService (place / change)
                        └─ CreateOrderAction / UpdateOrderAction
                           └─ DB::transaction → Pipeline → OrderCreated / OrderUpdated
                              → EIN Listener → EINE Log-Zeile [Domain-Event]
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

*Aufwärmübung „Töte den Parameter-Wust":* In `app/Exercises/CustomerRegistrar.php` liegt eine Methode mit sechs losen Parametern (ein „Data Clump") und ein Aufruf, in dem zwei Argumente vertauscht sind – vom Compiler unbemerkt. Refaktoriere die Signatur auf ein einziges `RegisterCustomerData`-DTO (`final readonly class`) und schreibe den Aufruf mit Named Arguments neu. Der Bug wird damit unmöglich. Verifizieren: `php artisan tinker` → `(new App\Exercises\CustomerRegistrar())->demoAufrufMitBug();`
→ `app/Exercises/CustomerRegistrar.php`, neu: `app/Data/RegisterCustomerData.php`

Diese Übung lässt sich **komplett im Browser** abarbeiten und testen – ganz ohne DB oder Seeding:

```bash
php artisan serve
# -> http://localhost:8000/uebung/1   (oder Tab "Übung 1: DTO")
```

Die Seite prüft deinen Code live per Reflection (DTO vorhanden? final + readonly? richtige Felder/Typen? Signatur umgestellt?) und führt den Beispielaufruf aus, sodass du siehst, ob die Vertauschung behoben ist. Refactoren → Seite neu laden → alle Häkchen grün.

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
