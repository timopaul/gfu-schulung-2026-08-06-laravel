<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

use Closure;

/**
 * ÜBUNG 6B (Tag 2) – Advanced Pipeline: ein neuer Pipe im Checkout.
 *
 * Erzwingt einen Mindestbestellwert auf die Zwischensumme (subtotalCents).
 * Muss NACH CheckStockPipe laufen, denn erst dort ist subtotalCents gesetzt.
 *
 * TODO:
 *   - wenn $context->subtotalCents < MIN_SUBTOTAL_CENTS:
 *       throw BelowMinimumOrderException::below(self::MIN_SUBTOTAL_CENTS, $context->subtotalCents);
 *   - sonst: return $next($context);
 */
final class MinimumOrderValuePipe
{
    private const MIN_SUBTOTAL_CENTS = 500;

    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        // TODO: Mindestwert prüfen und ggf. BelowMinimumOrderException werfen

        return $next($context);
    }
}
