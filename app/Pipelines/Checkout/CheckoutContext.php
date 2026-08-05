<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

use App\Data\CreateOrderData;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Block 4 – Der "Koffer", der durch die Pipeline gereicht wird.
 *
 * Anders als die readonly-Eingabe-DTOs ist dieser Kontext bewusst mutierbar:
 * Jeder Pipe reichert ihn an (Zwischensummen, Rabatt, Steuer). Am Ende der
 * Pipeline enthält er das vollständige, berechnete Ergebnis.
 */
final class CheckoutContext
{
    /** @var Collection<int, Product> nach product_id indizierte, gesperrte Produkte */
    public Collection $products;

    public int $subtotalCents = 0;
    public int $discountCents = 0;
    public int $taxCents = 0;

    public function __construct(public readonly CreateOrderData $data)
    {
        $this->products = collect();
    }

    public function totalCents(): int
    {
        return $this->subtotalCents - $this->discountCents + $this->taxCents;
    }
}
