@extends('layouts.app')

@section('title', 'Übung 4: Event & Listener')

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

    <h1>Übung 4 · Entkopple die Nebeneffekte über ein Event</h1>

    @if ($allPassed)
        <div class="banner done">Bestanden – ship() feuert nur noch OrderShipped, und zwei Listener erledigen Mail und Bestand. Ein Event, mehrere Reaktionen.</div>
    @else
        <div class="banner todo">Noch nicht fertig. Feuere das Event, verlagere die Nebeneffekte in Listener und registriere sie – dann neu laden.</div>
    @endif

    <div class="two-col">
        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Aufgabe</h2>
            <p class="muted" style="margin-top:0;">
                In <code>app/Exercises/OrderShipper.php</code> erledigt <code>ship()</code> zwei
                Nebeneffekte selbst: Versandbestätigung (Mail) und Bestandsaktualisierung. Beide sind
                Reaktionen auf „Bestellung versandt" – sie gehören in Listener.
            </p>
            <ol class="steps">
                <li>Lege das Event <code>app/Events/OrderShipped.php</code> an (readonly Payload: <code>email</code>, <code>sku</code>, <code>quantity</code>; <code>use Dispatchable, SerializesModels</code>).</li>
                <li>Zieh die Nebeneffekte in je einen Listener: <code>SendShippingConfirmation</code> und <code>UpdateInventory</code>, jeweils mit <code>handle(OrderShipped $event)</code>.</li>
                <li>Registriere beide für <code>OrderShipped</code> im <code>EventServiceProvider</code> (<code>$listen</code>).</li>
                <li><code>ship()</code> feuert nur noch <code>OrderShipped::dispatch($email, $sku, $quantity)</code>.</li>
            </ol>
            <p class="muted" style="margin-bottom:0;">
                Bonus: Warum kannst du jetzt einen dritten Listener (z. B. Statistik) ergänzen, ohne
                <code>ship()</code> anzufassen? Was ändert sich, wenn ein Listener <code>ShouldQueue</code> implementiert?
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

        <p class="muted" style="margin-bottom:6px;">Letzter Lauf von <code>ship()</code> (aus dem Log):</p>
        @if ($demoError)
            <div class="flash flash-err" style="margin:0;">
                Der Aufruf hat einen Fehler geworfen – vermutlich ein Zwischenstand beim Refactoring:<br>
                <code>{{ $demoError }}</code>
            </div>
        @elseif ($demo)
            <pre>Bestellung versandt:   {{ ($demo['shipped'] ?? false) ? 'ja' : '—' }}
Versandbestätigung:    {{ ($demo['mail'] ?? false) ? 'ja' : '—' }}
Bestand aktualisiert:  {{ ($demo['stock'] ?? false) ? 'ja' : '—' }}</pre>
        @else
            <p class="muted" style="margin:0;">Noch keine Log-Zeile gefunden. Lade neu.</p>
        @endif

        <div style="margin-top:16px;">
            <a class="btn btn-primary" href="{{ route('exercises.event') }}">Erneut prüfen</a>
        </div>
    </div>

    <p class="muted" style="margin-top:16px;">
        Alternativ auf der Konsole prüfbar:
        <code>php artisan tinker</code> →
        <code>app(App\Exercises\OrderShipper::class)->ship('kunde@example.test', 'SKU-42', 3);</code>
    </p>
@endsection
