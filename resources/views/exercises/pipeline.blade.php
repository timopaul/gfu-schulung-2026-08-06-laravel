@extends('layouts.app')

@section('title', 'Übung 6: Pipeline')

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

    <h1>Übung 6 · Ein neuer Pipe in der Checkout-Pipeline</h1>

    @if ($allPassed)
        <div class="banner done">Bestanden – der Mindestbestellwert-Pipe wirft sauber, lässt gültige Bestellungen durch und sitzt an der richtigen Stelle in der Pipeline.</div>
    @else
        <div class="banner todo">Noch nicht fertig. Bau die Exception, fülle den Pipe und häng ihn nach CheckStockPipe in die Pipeline – dann neu laden.</div>
    @endif

    <div class="two-col">
        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Aufgabe</h2>
            <p class="muted" style="margin-top:0;">
                Ein Mindestbestellwert von <b>5,00 €</b> (500 Cent) auf die Zwischensumme. Der Skeleton-Pipe
                <code>app/Pipelines/Checkout/MinimumOrderValuePipe.php</code> lässt aktuell noch alles durch.
            </p>
            <ol class="steps">
                <li>Lege <code>app/Exceptions/BelowMinimumOrderException.php</code> an – erbt <code>DomainException</code>,
                    <code>status = 422</code>, <code>title = 'Below Minimum Order'</code>, mit statischer Factory
                    <code>below($minCents, $actualCents)</code>. Vorbild: <code>InvalidVoucherException</code>.</li>
                <li>In <code>MinimumOrderValuePipe::handle()</code>: unterschreitet <code>$context-&gt;subtotalCents</code>
                    den Mindestwert, wirf die Exception – sonst <code>return $next($context)</code>.</li>
                <li>Häng den Pipe in <code>CreateOrderAction::execute()</code> in das <code>through([...])</code> –
                    <b>nach</b> <code>CheckStockPipe</code> (erst dort steht <code>subtotalCents</code>).</li>
            </ol>
            <p class="muted" style="margin-bottom:0;">
                Aha: Der globale Exception-Handler macht aus der Exception automatisch RFC-7807-JSON – der Pipe
                muss nichts über HTTP wissen. Und die Position im Array <b>ist</b> die Geschäftslogik.
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
            Der Pipe wird isoliert getestet – ein Kontext mit gesetzter Zwischensumme läuft durch <code>handle()</code>:
        </p>
        @if ($demoError)
            <div class="flash flash-err" style="margin:0;">
                Der Aufruf hat einen Fehler geworfen – vermutlich ein Zwischenstand beim Refactoring:<br>
                <code>{{ $demoError }}</code>
            </div>
        @elseif ($demo)
            <pre>subtotal  3,00 € -> {{ ($demo['throwsBelow'] ?? false) ? 'BelowMinimumOrderException geworfen ✓' : 'kein Wurf — läuft noch durch ✗' }}
subtotal 20,00 € -> {{ ($demo['passesAbove'] ?? false) ? 'unverändert durchgereicht ✓' : 'nicht durchgereicht ✗' }}</pre>
        @else
            <p class="muted" style="margin:0;">Noch keine Messung – lade neu.</p>
        @endif

        <div style="margin-top:16px;">
            <a class="btn btn-primary" href="{{ route('exercises.pipeline') }}">Erneut prüfen</a>
        </div>
    </div>

    <p class="muted" style="margin-top:16px;">
        End-to-End prüfbar per API: eine Bestellung unter 5 € anlegen →
        <code>HTTP 422</code> mit <code>"title": "Below Minimum Order"</code>.
    </p>
@endsection
