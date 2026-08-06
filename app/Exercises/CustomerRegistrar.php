<?php

declare(strict_types=1);

namespace App\Exercises;

use Illuminate\Support\Facades\Log;

/**
 * ÜBUNG (Block 1) – "Töte den Parameter-Wust".
 *
 * Diese Methode ist ABSICHTLICH schlecht: sechs lose Parameter ("Data Clump"),
 * die meisten gleich getypt (string). Die Reihenfolge ist die einzige "Doku" –
 * und genau da schleicht sich der Bug ein (siehe demoAufrufMitBug()).
 *
 * Deine Aufgabe:
 *   1. Erkenne den Bug im Aufruf unten: Stadt & Nachname sind vertauscht.
 *      Der Compiler merkt NICHTS, weil beides string ist.
 *   2. Ersetze die Signatur durch EIN typsicheres DTO:
 *          public function register(RegisterCustomerData $data): void
 *   3. Baue app/Data/RegisterCustomerData.php als `final readonly class`
 *      mit benannten, typisierten Feldern (email, firstName, lastName,
 *      street, city, newsletter mit Default false).
 *   4. Schreibe den Aufruf mit Named Arguments neu – die Vertauschung
 *      wird damit unmöglich, und das IDE vervollständigt jedes Feld.
 *
 * Bonus: Warum ist `string $email` immer noch schwach? Wie würde ein eigenes
 * Value Object (z. B. eine Email-Klasse) das Problem "ungültige Adresse"
 * bereits im Konstruktor abfangen?
 *
 * Verifizieren (php artisan tinker):
 *   (new App\Exercises\CustomerRegistrar())->demoAufrufMitBug();
 *   // danach: tail -f storage/logs/laravel.log  ->  [Übung] Kunde registriert
 */
final class CustomerRegistrar
{
    public function register(
        string $email,
        string $firstName,
        string $lastName,
        string $street,
        string $city,
        bool $newsletter = false,
    ): void {
        Log::info('[Übung] Kunde registriert', [
            'email' => $email,
            'name' => $firstName.' '.$lastName,
            'adresse' => $street.', '.$city,
            'newsletter' => $newsletter,
        ]);
    }

    /**
     * Der verräterische Aufruf: liest sich harmlos, ist aber falsch.
     * 'Köln' landet im Nachnamen, 'Muster' in der Stadt – niemand merkt es,
     * bis der Kunde plötzlich "Erika Köln" heißt und in "Muster" wohnt.
     *
     * Nach dem Refactoring auf Named Arguments kann dieser Fehler nicht mehr
     * passieren, ohne dass er sofort ins Auge springt.
     */
    public function demoAufrufMitBug(): void
    {
        $this->register('erika@example.test', 'Erika', 'Köln', 'Muster', 'Hauptstr. 1', true);
    }
}
