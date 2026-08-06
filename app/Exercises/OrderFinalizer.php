<?php

declare(strict_types=1);

namespace App\Exercises;

use Illuminate\Support\Facades\Log;

/**
 * ÜBUNG 3 (Block 2) – "Mach daraus eine Single Action".
 *
 * finalize() erledigt mehrere Schritte nacheinander (Nummer vergeben, Status
 * setzen, protokollieren) – ungeklammert, mitten in der Klasse. Genau EIN
 * Use-Case, der atomar laufen sollte: gehört in eine Action.
 *
 * Aufgabe:
 *   1. Lege app/Actions/Orders/FinalizeOrderAction.php an mit
 *          public function execute(string $email): string
 *      und verschiebe die Schritte dorthin.
 *   2. Klammere die Schritte in DB::transaction() (Atomarität).
 *   3. Injiziere die Action per Konstruktor in OrderFinalizer;
 *      finalize() delegiert nur noch an $this->action->execute($email).
 *
 * Verifizieren: /uebung/3 im Browser – refactoren, neu laden, grün werden.
 * (Diese Übung braucht die DB: einmalig `php artisan migrate`.)
 */
final class OrderFinalizer
{
    public function finalize(string $email): string
    {
        $orderNumber = 'ORD-'.strtoupper(substr(md5($email), 0, 6));
        $status = 'confirmed';

        Log::info('[Übung] Bestellung abgeschlossen', [
            'email' => $email,
            'order' => $orderNumber,
            'status' => $status,
        ]);

        return $orderNumber;
    }
}
