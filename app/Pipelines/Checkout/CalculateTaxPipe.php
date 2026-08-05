<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

use Closure;

/**
 * Block 4 – Pipe 3: berechnet 19 % USt. auf den rabattierten Betrag.
 */
final class CalculateTaxPipe
{
    private const TAX_RATE = 0.19;

    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $taxable = $context->subtotalCents - $context->discountCents;
        $context->taxCents = (int) round($taxable * self::TAX_RATE);

        return $next($context);
    }
}
