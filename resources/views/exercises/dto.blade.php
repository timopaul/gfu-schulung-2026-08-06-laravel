@extends('layouts.app')

@section('title', 'Übung 1: DTO-Refactoring')

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

    <h1>Übung 1 · Töte den Parameter-Wust (DTO)</h1>

    @if ($allPassed)
        <div class="banner done">Bestanden – alle Prüfungen grün. Der vertauschte Aufruf kann jetzt nicht mehr unbemerkt passieren.</div>
    @else
        <div class="banner todo">Noch nicht fertig. Refactore den Code und lade die Seite neu, bis alles grün ist.</div>
    @endif

    <div class="two-col">
        <div class="card">
            <h2 style="margin-top:0; font-size:16px;">Aufgabe</h2>
            <p class="muted" style="margin-top:0;">
                In <code>app/Exercises/CustomerRegistrar.php</code> steht eine Methode mit sechs losen
                Parametern (ein „Data Clump"). Im Beispielaufruf sind <strong>Stadt und Nachname vertauscht</strong> –
                der Compiler merkt nichts, weil beides <code>string</code> ist.
            </p>
            <ol class="steps">
                <li>Lege <code>app/Data/RegisterCustomerData.php</code> als <code>final readonly class</code> an – mit den Feldern <code>email</code>, <code>firstName</code>, <code>lastName</code>, <code>street</code>, <code>city</code> (alle <code>string</code>) und <code>newsletter</code> (<code>bool</code>, Default <code>false</code>).</li>
                <li>Stelle die Signatur um auf <code>register(RegisterCustomerData $data): void</code> und nutze im Rumpf die DTO-Felder.</li>
                <li>Schreibe <code>demoAufrufMitBug()</code> mit <strong>Named Arguments</strong> neu und korrigiere dabei die Vertauschung.</li>
            </ol>
            <p class="muted" style="margin-bottom:0;">
                Bonus: Warum ist <code>string $email</code> noch schwach – und was würde ein eigenes Value Object besser machen?
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

        <p class="muted" style="margin-bottom:6px;">Aktuelle Signatur von <code>register()</code>:</p>
        <div class="sig">{{ $signature }}</div>

        <p class="muted" style="margin:16px 0 6px;">Letzter Beispielaufruf (aus dem Log):</p>
        @if ($demoError)
            <div class="flash flash-err" style="margin:0;">
                Der Aufruf hat einen Fehler geworfen – vermutlich ein Zwischenstand beim Refactoring:<br>
                <code>{{ $demoError }}</code>
            </div>
        @elseif ($demo)
            <pre>Name:    {{ $demo['name'] ?? '—' }}
Adresse: {{ $demo['adresse'] ?? '—' }}
E-Mail:  {{ $demo['email'] ?? '—' }}</pre>
            @unless ($allPassed)
                <p class="muted" style="margin-bottom:0;">
                    Fällt dir was auf? Solange „Köln" im Namen und „Muster" in der Adresse steht, ist die Vertauschung noch drin.
                </p>
            @endunless
        @else
            <p class="muted" style="margin:0;">Noch keine Log-Zeile gefunden. Führe die Übung aus oder lade neu.</p>
        @endif

        <div style="margin-top:16px;">
            <a class="btn btn-primary" href="{{ route('exercises.dto') }}">Erneut prüfen</a>
        </div>
    </div>

    <p class="muted" style="margin-top:16px;">
        Alternativ auf der Konsole prüfbar:
        <code>php artisan tinker</code> →
        <code>(new App\Exercises\CustomerRegistrar())->demoAufrufMitBug();</code>
    </p>
@endsection
