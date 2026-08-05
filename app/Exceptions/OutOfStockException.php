<?php

declare(strict_types=1);

namespace App\Exceptions;

class OutOfStockException extends DomainException
{
    public int $status = 409;
    public string $title = 'Out Of Stock';

    public static function forProduct(string $sku, int $requested, int $available): self
    {
        $e = new self(sprintf(
            'Produkt %s: angefordert %d, verfügbar %d.',
            $sku,
            $requested,
            $available,
        ));

        return $e;
    }
}
