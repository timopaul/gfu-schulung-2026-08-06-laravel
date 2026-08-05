<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

use App\Exceptions\OutOfStockException;
use App\Models\Product;
use Closure;

/**
 * Block 4 – Pipe 1: lädt die Produkte (mit Sperre) und prüft Bestände.
 * Nebenbei wird die Zwischensumme (subtotal) berechnet.
 */
final class CheckStockPipe
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $productIds = array_map(
            static fn ($item) => $item->productId,
            $context->data->items,
        );

        // lockForUpdate: verhindert Überverkauf bei parallelen Checkouts.
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $subtotal = 0;

        foreach ($context->data->items as $item) {
            /** @var Product $product */
            $product = $products->get($item->productId);

            if ($product === null || $product->stock < $item->quantity) {
                throw OutOfStockException::forProduct(
                    $product?->sku ?? "id:{$item->productId}",
                    $item->quantity,
                    $product?->stock ?? 0,
                );
            }

            $subtotal += $product->price_cents * $item->quantity;
        }

        $context->products = $products;
        $context->subtotalCents = $subtotal;

        return $next($context);
    }
}
