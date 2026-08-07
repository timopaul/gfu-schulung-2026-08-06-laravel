@extends('layouts.app')

@section('title', 'Übung 5: Eloquent & N+1')

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

    <h1>Übung 5 · Baue den Report ohne N+1</h1>

    @if ($allPassed)
        <div class="banner done">Bestanden – summarize() filtert im SQL, zählt per Subquery und kommt mit einer einzigen Query aus.</div>
    @else
        <div class="banner todo">Noch nicht fertig. Filtere über den Builder, zähle per withItemCount() und lade neu.</div>
    @endif

    <div class="two-col">
        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Aufgabe</h2>
            <p class="muted" style="margin-top:0;">
                In <code>app/Exercises/OrderReport.php</code> lädt <code>summarize()</code> per
                <code>Order::all()</code> alle Bestellungen aller Mandanten, filtert Mandant und Status
                in PHP und ruft pro Zeile <code>$order-&gt;items()-&gt;count()</code> auf – das klassische N+1.
            </p>
            <ol class="steps">
                <li>Query über den Builder: <code>Order::query()-&gt;forTenant($tenantId)-&gt;paid()</code>.</li>
                <li>Item-Anzahl per Count-Subquery: <code>-&gt;withItemCount()</code> – danach steht die Zahl in <code>$order-&gt;items_count</code>.</li>
                <li>Ergebnis wie gehabt zurückgeben (<code>id</code>, <code>items</code>, <code>total_eur</code>), nur eben aus der optimierten Query.</li>
            </ol>
            <p class="muted" style="margin-bottom:0;">
                Bonus: Aktiviere <code>Model::preventLazyLoading()</code> im <code>AppServiceProvider</code> (nur außerhalb Production).
                Ein versehentlicher Lazy-Load fliegt dann sofort als Exception auf, statt still N+1 zu erzeugen.
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
            Messung an temporären Testdaten (in einer Transaktion, danach zurückgerollt – deine DB bleibt unangetastet):
        </p>
        @if ($demoError)
            <div class="flash flash-err" style="margin:0;">
                Der Aufruf hat einen Fehler geworfen – vermutlich ein Zwischenstand beim Refactoring:<br>
                <code>{{ $demoError }}</code>
            </div>
        @elseif ($demo)
            <pre>Queries für summarize():  {{ $demo['queries'] ?? '—' }}   (Ziel: 1, unoptimiert: 1 + N)
Zeilen im Report:         {{ $demo['rows'] ?? '—' }}   (erwartet: 2 bezahlte Bestellungen)
items_count je Zeile:     {{ !empty($demo['counts']) ? implode(', ', $demo['counts']) : '—' }}   (erwartet: 2, 3)</pre>
        @else
            <p class="muted" style="margin:0;">Noch keine Messung – lade neu.</p>
        @endif

        <div style="margin-top:16px;">
            <a class="btn btn-primary" href="{{ route('exercises.eloquent') }}">Erneut prüfen</a>
        </div>
    </div>

    <p class="muted" style="margin-top:16px;">
        Alternativ auf der Konsole prüfbar:
        <code>php artisan tinker</code> →
        <code>DB::enableQueryLog(); app(App\Exercises\OrderReport::class)->summarize(1); count(DB::getQueryLog());</code>
    </p>
@endsection
