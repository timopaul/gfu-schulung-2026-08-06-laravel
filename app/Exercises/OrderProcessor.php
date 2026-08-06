<?php

declare(strict_types=1);

namespace App\Exercises;

use Illuminate\Support\Facades\Log;

/**
 * ÜBUNG 2 (Block 1) – "Zieh die Rechenlogik in einen Service".
 *
 * process() macht zu viel: es hält Daten UND rechnet die Summe selbst aus.
 * Reine Fachlogik (Summe + Rabatt) gehört in einen eigenen Service.
 *
 * Aufgabe:
 *   1. Lege app/Services/PricingService.php an mit
 *          public function total(array $items, float $discountRate): float
 *      und verschiebe die Rechnung dorthin.
 *   2. Injiziere den PricingService in OrderProcessor per Konstruktor.
 *   3. process() ruft nur noch $this->pricing->total(...) auf.
 *
 * Verifizieren: /uebung/2 im Browser – refactoren, neu laden, grün werden.
 */
final class OrderProcessor
{
    public function process(): void
    {
        $items = [
            ['name' => 'Kaffee', 'price' => 4.50, 'qty' => 2],
            ['name' => 'Kuchen', 'price' => 3.20, 'qty' => 1],
        ];
        $discountRate = 0.10;

        // Diese Rechnung soll in den PricingService wandern.
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item['price'] * $item['qty'];
        }
        $total = $sum * (1 - $discountRate);

        Log::info('[Übung] Bestellung berechnet', [
            'summe' => round($sum, 2),
            'rabatt' => $discountRate,
            'gesamt' => round($total, 2),
        ]);
    }
}
