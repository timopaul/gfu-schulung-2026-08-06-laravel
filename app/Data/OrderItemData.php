<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Block 1 – Typsicheres DTO für eine einzelne Bestellposition.
 *
 * Readonly-Properties (PHP 8.2+) machen das Objekt unveränderlich:
 * einmal erzeugt, kann keine Position mehr "aus Versehen" mutiert werden.
 */
final readonly class OrderItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }

    /** @param array{product_id:int, quantity:int} $row */
    public static function fromArray(array $row): self
    {
        return new self(
            productId: (int) $row['product_id'],
            quantity: (int) $row['quantity'],
        );
    }
}
