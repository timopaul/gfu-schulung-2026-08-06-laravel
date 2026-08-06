<?php

declare(strict_types=1);

namespace App\Exercises;

use Illuminate\Support\Facades\Log;

/**
 * ÜBUNG 4 (Block 2) – "Entkopple die Nebeneffekte über ein Event".
 *
 * ship() erledigt zwei Nebeneffekte selbst und inline: Versandbestätigung
 * (Mail) und Bestandsaktualisierung. Beide gehören nicht in die Kernmethode –
 * sie sind Reaktionen auf "Bestellung versandt".
 *
 * Aufgabe:
 *   1. Lege das Event app/Events/OrderShipped.php an (readonly Payload:
 *      email, sku, quantity; use Dispatchable, SerializesModels).
 *   2. Zieh die zwei Nebeneffekte in je einen Listener:
 *        - app/Listeners/SendShippingConfirmation.php  -> loggt Versandbestätigung
 *        - app/Listeners/UpdateInventory.php           -> loggt Bestand aktualisiert
 *   3. Registriere beide für OrderShipped im EventServiceProvider ($listen).
 *   4. ship() feuert nur noch OrderShipped::dispatch(...) statt die
 *      Nebeneffekte selbst zu erledigen.
 *
 * Verifizieren: /uebung/4 im Browser – refactoren, neu laden, grün werden.
 */
final class OrderShipper
{
    public function ship(string $email, string $sku, int $quantity): void
    {
        Log::info('[Übung] Bestellung versandt', [
            'email' => $email,
            'sku' => $sku,
            'qty' => $quantity,
        ]);

        // Nebeneffekt 1 – soll in einen Listener wandern.
        Log::info('[Übung] Versandbestätigung', ['email' => $email]);

        // Nebeneffekt 2 – soll in einen Listener wandern.
        Log::info('[Übung] Bestand aktualisiert', ['sku' => $sku, 'abgezogen' => $quantity]);
    }
}
