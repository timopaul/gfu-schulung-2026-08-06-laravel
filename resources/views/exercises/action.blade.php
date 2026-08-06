@extends('layouts.app')

@section('title', 'Übung 3: Single Action')

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
        .sig { background: #EEF1F7; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; font-size: 13px; }
        ol.steps { margin: 0; padding-left: 20px; }
        ol.steps li { margin-bottom: 8px; font-size: 14px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 720px) { .two-col { grid-template-columns: 1fr; } }
    </style>

    <h1>Übung 3 · Mach daraus eine Single Action</h1>

    @if ($allPassed)
        <div class="banner done">Bestanden – ein Use-Case, eine Action, alles in einer Transaktion, sauber per DI eingehängt. Genau wie CreateOrderAction, nur kleiner.</div>
    @else
        <div class="banner todo">Noch nicht fertig. Zieh die Schritte in die Action und lade neu, bis alles grün ist.</div>
    @endif

    <div class="two-col">
        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Aufgabe</h2>
            <p class="muted" style="margin-top:0;">
                In <code>app/Exercises/OrderFinalizer.php</code> erledigt <code>finalize()</code> mehrere
                Schritte inline und ungeklammert. Das ist genau EIN Use-Case, der atomar laufen sollte –
                also eine Single Action.
            </p>
            <ol class="steps">
                <li>Lege <code>app/Actions/Orders/FinalizeOrderAction.php</code> an mit <code>execute(string $email): string</code> und verschiebe die Schritte dorthin.</li>
                <li>Klammere die Schritte in <code>DB::transaction(fn () =&gt; …)</code> – so laufen sie ganz oder gar nicht.</li>
                <li>Injiziere die Action per Konstruktor in <code>OrderFinalizer</code>; <code>finalize()</code> delegiert nur noch.</li>
            </ol>
            <p class="muted" style="margin-bottom:0;">
                Vorbild ist <code>app/Actions/Orders/CreateOrderAction.php</code>. Bonus: Warum genau EINE
                öffentliche Methode? Was bringt die Transaktion, wenn Schritt 2 fehlschlägt?
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

        <p class="muted" style="margin-bottom:6px;">Aktueller Konstruktor von <code>OrderFinalizer</code>:</p>
        <div class="sig">{{ $constructor }}</div>

        <p class="muted" style="margin:16px 0 6px;">Letzter Lauf von <code>finalize()</code> (aus dem Log):</p>
        @if ($demoError)
            <div class="flash flash-err" style="margin:0;">
                Der Aufruf hat einen Fehler geworfen – bei einem DB-Fehler einmalig <code>php artisan migrate</code> ausführen:<br>
                <code>{{ $demoError }}</code>
            </div>
        @elseif ($demo)
            <pre>E-Mail: {{ $demo['email'] ?? '—' }}
Nummer: {{ $demo['order'] ?? '—' }}
Status: {{ $demo['status'] ?? '—' }}</pre>
        @else
            <p class="muted" style="margin:0;">Noch keine Log-Zeile gefunden. Lade neu.</p>
        @endif

        <div style="margin-top:16px;">
            <a class="btn btn-primary" href="{{ route('exercises.action') }}">Erneut prüfen</a>
        </div>
    </div>

    <p class="muted" style="margin-top:16px;">
        Alternativ auf der Konsole prüfbar:
        <code>php artisan tinker</code> →
        <code>app(App\Exercises\OrderFinalizer::class)->finalize('kunde@example.test');</code>
    </p>
@endsection
