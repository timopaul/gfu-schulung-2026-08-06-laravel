<?php

declare(strict_types=1);

namespace App\Pipelines\Checkout;

use App\Exceptions\InvalidVoucherException;
use App\Models\Voucher;
use Closure;

/**
 * Block 4 – Pipe 2: wendet einen optionalen Gutschein an.
 */
final class ApplyDiscountsPipe
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $code = $context->data->voucherCode;

        if ($code === null) {
            return $next($context);
        }

        $voucher = Voucher::query()->where('code', $code)->first();

        if ($voucher === null || ! $voucher->isRedeemable()) {
            throw InvalidVoucherException::notRedeemable($code);
        }

        $context->discountCents = (int) round(
            $context->subtotalCents * $voucher->percent_off / 100,
        );

        return $next($context);
    }
}
