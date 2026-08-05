<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvalidVoucherException extends DomainException
{
    public int $status = 422;
    public string $title = 'Invalid Voucher';

    public static function notRedeemable(string $code): self
    {
        return new self("Gutschein {$code} ist nicht einlösbar.");
    }
}
