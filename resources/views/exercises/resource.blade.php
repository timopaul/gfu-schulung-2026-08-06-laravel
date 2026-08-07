@extends('layouts.app')

@section('title', 'Übung 7: Resources')

@section('content')
    <style>
        .checklist { list-style: none; margin: 0; padding: 0; }
        .checklist li { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .checklist li:last-child { border-bottom: none; }
        .mark { width: 22px; height: 22px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex: 0 0 auto; color: #fff; }
        .mark.ok { background: var(--green); }
        .mark.no { background: var(--red); }
        .banner { padding: 16px 18px; border-radius: 10px; font-weight: 600; margin-bottom: 20px; }
        .banner.done { background: #E7F6EC; color: var(--green); border: 1px solid #BFE6CC; }
        .banner.todo { background: #FBEAE8; color: var(--red); border: 1px solid #F2C7C2; }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        pre { background: var(--navy); color: #E6ECF7; padding: 14px 16px; border-radius: 10px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
        ol.steps { margin: 0; padding-left: 20px; }
        ol.steps li { margin-bottom: 8px; font-size: 14px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 720px) { .two-col { grid-template-columns: 1fr; } }
    </style>

    <h1>Übung 7 · API Architecture &amp; Resources</h1>

    @if ($allPassed)
        <div class="banner done">Bestanden – die ProductResource formt bewusst (Euro-Float, in_stock, bedingtes stock), interne Felder bleiben draußen, und der Endpoint hängt am aktuellen Mandanten.</div>
    @else
        <div class="banner todo">Noch nicht fertig. Form die Resource um, häng den Controller an den Mandanten und trag die Route ein – dann neu laden.</div>
    @endif

    <div class="two-col">
        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Aufgabe</h2>
            <p class="muted" style="margin-top:0;">
                <code>ProductResource::toArray()</code> reicht aktuell mit <code>parent::toArray()</code> <b>alle</b>
                DB-Spalten roh nach außen – inklusive interner <code>tenant_id</code> und Cent-Beträge. Entkopple
                die Außendarstellung von der DB-Struktur – Vorbild: <code>OrderResource</code>.
            </p>
            <ol class="steps">
                <li>In <code>ProductResource::toArray()</code> bewusst formen: <code>price</code> als Euro-Float
                    (<code>price_cents / 100</code>) plus <code>'currency' =&gt; 'EUR'</code>, <code>in_stock</code>
                    als Boolean (<code>$this-&gt;stock &gt; 0</code>), <code>stock</code> nur per
                    <code>$this-&gt;when(...)</code> bei Vorrat – <code>tenant_id</code> und Timestamps weglassen.</li>
                <li>In <code>ProductController::index()</code> die Produkte des aktuellen Mandanten laden
                    (<code>$request-&gt;attributes-&gt;get('tenant_id')</code>) und
                    <code>ProductResource::collection(...)</code> zurückgeben.</li>
                <li>Route ergänzen: <code>GET products</code> in die bestehende v1-Gruppe in
                    <code>routes/api.php</code>.</li>
            </ol>
            <p class="muted" style="margin-bottom:0;">
                Aha: Die Resource ist die einzige Stelle, die entscheidet, was der Client sieht. Interne Spalten
                bleiben intern, ohne dass die DB oder das Model etwas dafür tun müssen.
            </p>
        </div>

        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Prüfungen</h2>
            <ul class="checklist">
                @foreach ($checks as $check)
                    <li>
                        <span class="mark {{ $check['ok'] ? 'ok' : 'no' }}">{{ $check['ok'] ? '✓' : '×' }}</span>
                        <span>{{ $check['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h2 style="margin-top:0; font-size:16px;">Aktueller Stand</h2>

        <p class="muted" style="margin-bottom:6px;">
            Zwei Beispielprodukte laufen durch die Resource – einmal mit Vorrat (5), einmal ausverkauft (0):
        </p>
        @if ($demoError)
            <div class="flash flash-err" style="margin:0;">
                Der Aufruf hat einen Fehler geworfen – vermutlich ein Zwischenstand beim Refactoring:<br>
                <code>{{ $demoError }}</code>
            </div>
        @elseif ($demo)
            <div class="two-col">
                <div>
                    <p class="muted" style="margin:0 0 4px;">mit Vorrat (stock 5, 19,99 €):</p>
                    <pre>{{ json_encode($demo['high'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                <div>
                    <p class="muted" style="margin:0 0 4px;">ausverkauft (stock 0):</p>
                    <pre>{{ json_encode($demo['low'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @else
            <p class="muted" style="margin:0;">Noch keine Messung – lege die Resource an und lade neu.</p>
        @endif

        <div style="margin-top:16px;">
            <a class="btn btn-primary" href="{{ route('exercises.resource') }}">Erneut prüfen</a>
        </div>
    </div>

    <p class="muted" style="margin-top:16px;">
        End-to-End prüfbar per API: <code>GET /api/v1/products</code> mit gültigem <code>X-Api-Key</code> →
        nur <code>id, name, sku, price, currency, in_stock</code> (und <code>stock</code> bei Vorrat), <b>kein</b>
        <code>tenant_id</code>, <b>keine</b> rohen <code>*_cents</code>.
    </p>
@endsection
