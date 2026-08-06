<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exercises\CustomerRegistrar;
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
