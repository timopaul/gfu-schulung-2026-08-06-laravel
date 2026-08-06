<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exercises\CustomerRegistrar;
use App\Exercises\OrderFinalizer;
use App\Exercises\OrderProcessor;
use Illuminate\View\View;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

/**
 * Web-Runner für die Aufwärmübung von Tag 1 (Block 1 – DTOs).
 *
 * Die Seite prüft per Reflection den Ist-Zustand des Codes und meldet grün/rot:
 *  - existiert App\Data\RegisterCustomerData?
 *  - ist die Klasse final + readonly mit den richtigen, typisierten Feldern?
 *  - nimmt CustomerRegistrar::register() nur noch EIN Argument vom DTO-Typ?
 *  - liefert der (refaktorierte) Beispielaufruf jetzt korrekte Werte,
 *    d. h. ist der vertauschte Name/Adresse-Bug behoben?
 *
 * Bewusst OHNE Datenbank/Mandant: die Übung läuft direkt nach dem Clonen,
 * ohne Seeding. Refactoren -> Seite neu laden -> bis alles grün ist.
 */
final class ExerciseWebController
{
    private const DTO = 'App\\Data\\RegisterCustomerData';

    /** Erwartete Felder des Ziel-DTO: name => Typ */
    private const EXPECTED_FIELDS = [
        'email' => 'string',
        'firstName' => 'string',
        'lastName' => 'string',
        'street' => 'string',
        'city' => 'string',
        'newsletter' => 'bool',
    ];

    /** Ziel-Service für Übung 2. */
    private const SERVICE = 'App\\Services\\PricingService';

    /** Beispieldaten für die Prüfung von PricingService::total(). */
    private const SAMPLE_ITEMS = [
        ['name' => 'Kaffee', 'price' => 4.50, 'qty' => 2],
        ['name' => 'Kuchen', 'price' => 3.20, 'qty' => 1],
    ];

    /** Ziel-Action für Übung 3. */
    private const ACTION = 'App\\Actions\\Orders\\FinalizeOrderAction';

    public function dtoRefactoring(): View
    {
        $checks = $this->buildChecks();
        [$demo, $demoError] = $this->runDemo();

        // Prüft, ob der vertauschte Aufruf behoben wurde.
        $demoFixed = $demo !== null
            && ($demo['name'] ?? null) === 'Erika Muster'
            && str_contains((string) ($demo['adresse'] ?? ''), 'Hauptstr.')
            && str_contains((string) ($demo['adresse'] ?? ''), 'Köln');

        $checks[] = [
            'label' => 'Beispielaufruf korrekt (Name "Erika Muster", Adresse "Hauptstr. 1, Köln")',
            'ok' => $demoFixed,
        ];

        $allPassed = collect($checks)->every(fn (array $c): bool => $c['ok'] === true);

        return view('exercises.dto', [
            'checks' => $checks,
            'demo' => $demo,
            'demoError' => $demoError,
            'allPassed' => $allPassed,
            'signature' => $this->currentSignature(),
        ]);
    }

    public function serviceExtraction(): View
    {
        $checks = $this->buildServiceChecks();
        [$demo, $demoError] = $this->runProcessDemo();

        // process() muss nach dem Refactoring dasselbe Ergebnis liefern.
        $demoOk = $demo !== null
            && isset($demo['gesamt'])
            && abs(((float) $demo['gesamt']) - 10.98) < 0.01;

        $checks[] = [
            'label' => 'process() liefert weiterhin das korrekte Ergebnis (10.98)',
            'ok' => $demoOk,
        ];

        $allPassed = collect($checks)->every(fn (array $c): bool => $c['ok'] === true);

        return view('exercises.service', [
            'checks' => $checks,
            'demo' => $demo,
            'demoError' => $demoError,
            'allPassed' => $allPassed,
            'constructor' => $this->currentConstructor(),
        ]);
    }

    /** @return array<int, array{label: string, ok: bool}> */
    private function buildServiceChecks(): array
    {
        $checks = [];

        $exists = class_exists(self::SERVICE);
        $checks[] = ['label' => 'Service "app/Services/PricingService.php" existiert', 'ok' => $exists];

        $hasTotal = false;
        $returnsFloat = false;
        $paramsOk = false;
        $computesOk = false;

        if ($exists && method_exists(self::SERVICE, 'total')) {
            $hasTotal = true;
            $rm = new ReflectionMethod(self::SERVICE, 'total');

            $rt = $rm->getReturnType();
            $returnsFloat = $rt !== null && ltrim((string) $rt, '?') === 'float';

            $params = $rm->getParameters();
            $paramsOk = count($params) === 2
                && $params[0]->getType() !== null && (string) $params[0]->getType() === 'array'
                && $params[1]->getType() !== null && ltrim((string) $params[1]->getType(), '?') === 'float';

            try {
                $result = app(self::SERVICE)->total(self::SAMPLE_ITEMS, 0.10);
                $computesOk = is_numeric($result) && abs((float) $result - 10.98) < 0.01;
            } catch (Throwable) {
                $computesOk = false;
            }
        }

        $checks[] = ['label' => 'Methode total() existiert', 'ok' => $hasTotal];
        $checks[] = ['label' => 'total() hat Rückgabetyp float', 'ok' => $returnsFloat];
        $checks[] = ['label' => 'Signatur total(array $items, float $discountRate)', 'ok' => $paramsOk];
        $checks[] = ['label' => 'total() rechnet korrekt (9.00 + 3.20, 10 % Rabatt = 10.98)', 'ok' => $computesOk];

        // Dependency Injection: OrderProcessor bekommt den Service per Konstruktor.
        $injected = false;
        $ctor = (new ReflectionClass(OrderProcessor::class))->getConstructor();
        if ($ctor !== null) {
            foreach ($ctor->getParameters() as $p) {
                $t = $p->getType();
                if ($t !== null && ltrim((string) $t, '?') === self::SERVICE) {
                    $injected = true;
                    break;
                }
            }
        }
        $checks[] = ['label' => 'OrderProcessor bekommt PricingService per Konstruktor (DI)', 'ok' => $injected];

        return $checks;
    }

    /**
     * Löst OrderProcessor über den Container auf und liest die zuletzt geloggte Zeile.
     *
     * @return array{0: ?array<string, mixed>, 1: ?string}
     */
    private function runProcessDemo(): array
    {
        try {
            app(OrderProcessor::class)->process();
        } catch (Throwable $e) {
            return [null, $e->getMessage()];
        }

        $logFile = storage_path('logs/laravel.log');
        if (! is_readable($logFile)) {
            return [null, null];
        }

        $lines = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        foreach ($lines as $line) {
            if (str_contains($line, '[Übung] Bestellung berechnet') && preg_match('/(\{.*\})\s*$/', $line, $m)) {
                $json = json_decode($m[1], true);

                return [is_array($json) ? $json : null, null];
            }
        }

        return [null, null];
    }

    /** Baut die aktuelle Konstruktor-Signatur von OrderProcessor als lesbaren String. */
    private function currentConstructor(): string
    {
        $ctor = (new ReflectionClass(OrderProcessor::class))->getConstructor();
        if ($ctor === null) {
            return 'OrderProcessor hat (noch) keinen Konstruktor';
        }

        $parts = [];
        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType() !== null ? (string) $p->getType().' ' : '';
            $parts[] = $type.'$'.$p->getName();
        }

        return '__construct('.implode(', ', $parts).')';
    }

    public function actionExtraction(): View
    {
        $checks = $this->buildActionChecks();
        [$demo, $demoError] = $this->runFinalizeDemo();

        // Verhalten muss erhalten bleiben: Status "confirmed", Nummer vergeben.
        $demoOk = $demo !== null
            && ($demo['status'] ?? null) === 'confirmed'
            && str_starts_with((string) ($demo['order'] ?? ''), 'ORD-');

        $checks[] = [
            'label' => 'finalize() liefert weiterhin dasselbe Ergebnis (Status "confirmed", Nummer "ORD-…")',
            'ok' => $demoOk,
        ];

        $allPassed = collect($checks)->every(fn (array $c): bool => $c['ok'] === true);

        return view('exercises.action', [
            'checks' => $checks,
            'demo' => $demo,
            'demoError' => $demoError,
            'allPassed' => $allPassed,
            'constructor' => $this->currentFinalizerConstructor(),
        ]);
    }

    /** @return array<int, array{label: string, ok: bool}> */
    private function buildActionChecks(): array
    {
        $checks = [];

        $exists = class_exists(self::ACTION);
        $checks[] = ['label' => 'Action "app/Actions/Orders/FinalizeOrderAction.php" existiert', 'ok' => $exists];

        $onePublic = false;
        $hasExecute = false;
        $signatureOk = false;
        $usesTransaction = false;

        if ($exists) {
            $rc = new ReflectionClass(self::ACTION);

            // Eine Action kapselt EINEN Use-Case: genau eine öffentliche Methode.
            $public = array_filter(
                $rc->getMethods(ReflectionMethod::IS_PUBLIC),
                fn (ReflectionMethod $m): bool => ! $m->isConstructor() && $m->getDeclaringClass()->getName() === self::ACTION,
            );
            $onePublic = count($public) === 1;

            if ($rc->hasMethod('execute')) {
                $hasExecute = true;
                $rm = $rc->getMethod('execute');

                $rt = $rm->getReturnType();
                $params = $rm->getParameters();
                $signatureOk = $rt !== null && ltrim((string) $rt, '?') === 'string'
                    && count($params) === 1
                    && $params[0]->getType() !== null && ltrim((string) $params[0]->getType(), '?') === 'string';

                $usesTransaction = str_contains($this->methodSource($rm), 'DB::transaction');
            }
        }

        $checks[] = ['label' => 'Genau eine öffentliche Methode (ein Use-Case)', 'ok' => $onePublic];
        $checks[] = ['label' => 'Methode execute() existiert', 'ok' => $hasExecute];
        $checks[] = ['label' => 'Signatur execute(string $email): string', 'ok' => $signatureOk];
        $checks[] = ['label' => 'execute() klammert die Schritte in DB::transaction()', 'ok' => $usesTransaction];

        // Dependency Injection: OrderFinalizer bekommt die Action per Konstruktor.
        $injected = false;
        $ctor = (new ReflectionClass(OrderFinalizer::class))->getConstructor();
        if ($ctor !== null) {
            foreach ($ctor->getParameters() as $p) {
                $t = $p->getType();
                if ($t !== null && ltrim((string) $t, '?') === self::ACTION) {
                    $injected = true;
                    break;
                }
            }
        }
        $checks[] = ['label' => 'OrderFinalizer bekommt FinalizeOrderAction per Konstruktor (DI)', 'ok' => $injected];

        return $checks;
    }

    /**
     * Löst OrderFinalizer über den Container auf und liest die zuletzt geloggte Zeile.
     *
     * @return array{0: ?array<string, mixed>, 1: ?string}
     */
    private function runFinalizeDemo(): array
    {
        try {
            app(OrderFinalizer::class)->finalize('kunde@example.test');
        } catch (Throwable $e) {
            return [null, $e->getMessage()];
        }

        $logFile = storage_path('logs/laravel.log');
        if (! is_readable($logFile)) {
            return [null, null];
        }

        $lines = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        foreach ($lines as $line) {
            if (str_contains($line, '[Übung] Bestellung abgeschlossen') && preg_match('/(\{.*\})\s*$/', $line, $m)) {
                $json = json_decode($m[1], true);

                return [is_array($json) ? $json : null, null];
            }
        }

        return [null, null];
    }

    /** Liest den Quelltext einer Methode (für den DB::transaction-Check). */
    private function methodSource(ReflectionMethod $rm): string
    {
        $file = $rm->getFileName();
        if ($file === false || ! is_readable($file)) {
            return '';
        }

        $lines = file($file) ?: [];
        $slice = array_slice($lines, $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1);

        return implode('', $slice);
    }

    /** Baut die aktuelle Konstruktor-Signatur von OrderFinalizer als lesbaren String. */
    private function currentFinalizerConstructor(): string
    {
        $ctor = (new ReflectionClass(OrderFinalizer::class))->getConstructor();
        if ($ctor === null) {
            return 'OrderFinalizer hat (noch) keinen Konstruktor';
        }

        $parts = [];
        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType() !== null ? (string) $p->getType().' ' : '';
            $parts[] = $type.'$'.$p->getName();
        }

        return '__construct('.implode(', ', $parts).')';
    }

    /** @return array<int, array{label: string, ok: bool}> */
    private function buildChecks(): array
    {
        $checks = [];

        $dtoExists = class_exists(self::DTO);
        $checks[] = ['label' => 'DTO "app/Data/RegisterCustomerData.php" existiert', 'ok' => $dtoExists];

        if ($dtoExists) {
            $rc = new ReflectionClass(self::DTO);

            $checks[] = ['label' => 'Klasse ist als final deklariert', 'ok' => $rc->isFinal()];

            // readonly: entweder "readonly class" ODER alle Properties readonly.
            $classReadonly = method_exists($rc, 'isReadOnly') && $rc->isReadOnly();
            $props = $rc->getProperties();
            $allPropsReadonly = $props !== []
                && collect($props)->every(fn (ReflectionProperty $p): bool => $p->isReadOnly());
            $checks[] = ['label' => 'Zustand ist unveränderlich (readonly)', 'ok' => $classReadonly || $allPropsReadonly];

            foreach (self::EXPECTED_FIELDS as $name => $type) {
                $ok = false;
                if ($rc->hasProperty($name)) {
                    $t = $rc->getProperty($name)->getType();
                    // akzeptiert string und ?string (nullable) für optionale Felder
                    $ok = $t !== null && ltrim((string) $t, '?') === $type;
                }
                $checks[] = ['label' => "Feld \${$name} vom Typ {$type}", 'ok' => $ok];
            }
        }

        // register() nimmt genau EIN Argument vom DTO-Typ.
        $oneParam = false;
        $paramTyped = false;
        if (method_exists(CustomerRegistrar::class, 'register')) {
            $params = (new ReflectionMethod(CustomerRegistrar::class, 'register'))->getParameters();
            $oneParam = count($params) === 1;
            if ($oneParam) {
                $t = $params[0]->getType();
                $paramTyped = $t !== null && ltrim((string) $t, '?') === self::DTO;
            }
        }
        $checks[] = ['label' => 'register() hat genau einen Parameter', 'ok' => $oneParam];
        $checks[] = ['label' => '... und der ist vom Typ RegisterCustomerData', 'ok' => $paramTyped];

        return $checks;
    }

    /**
     * Führt den Beispielaufruf aus und liest die zuletzt geloggte Zeile.
     *
     * @return array{0: ?array<string, mixed>, 1: ?string}
     */
    private function runDemo(): array
    {
        try {
            (new CustomerRegistrar())->demoAufrufMitBug();
        } catch (Throwable $e) {
            return [null, $e->getMessage()];
        }

        $logFile = storage_path('logs/laravel.log');
        if (! is_readable($logFile)) {
            return [null, null];
        }

        $lines = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        foreach ($lines as $line) {
            if (str_contains($line, '[Übung] Kunde registriert') && preg_match('/(\{.*\})\s*$/', $line, $m)) {
                $json = json_decode($m[1], true);

                return [is_array($json) ? $json : null, null];
            }
        }

        return [null, null];
    }

    /** Baut die aktuelle Signatur von register() als lesbaren String (Fortschrittsanzeige). */
    private function currentSignature(): string
    {
        if (! method_exists(CustomerRegistrar::class, 'register')) {
            return 'register(): Methode nicht gefunden';
        }

        $parts = [];
        foreach ((new ReflectionMethod(CustomerRegistrar::class, 'register'))->getParameters() as $p) {
            $type = $p->getType() !== null ? (string) $p->getType().' ' : '';
            $parts[] = $type.'$'.$p->getName();
        }

        return 'register('.implode(', ', $parts).')';
    }
}
