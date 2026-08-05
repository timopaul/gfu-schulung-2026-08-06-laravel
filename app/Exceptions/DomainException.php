<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Block 6 – Basisklasse aller fachlichen Fehler.
 * Wird im globalen Exception-Handler (bootstrap/app.php) zu einer
 * RFC-7807-konformen JSON-Antwort gerendert.
 */
abstract class DomainException extends RuntimeException
{
    /** HTTP-Status, den dieser Fehler nach außen erzeugt. */
    public int $status = 422;

    /** Kurzer, maschinenlesbarer Fehlertyp (RFC 7807 "type"/"title"). */
    public string $title = 'Domain Error';
}
