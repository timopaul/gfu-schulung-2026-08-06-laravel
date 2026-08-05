<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Data\CreateOrderData;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Pipelines\Checkout\ApplyDiscountsPipe;
use App\Pipelines\Checkout\CalculateTaxPipe;
use App\Pipelines\Checkout\CheckoutContext;
use App\Pipelines\Checkout\CheckStockPipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

/**
 * Block 2 + Block 4 – Eine Single-Action-Klasse kapselt EINEN Use-Case.
 *
 * Verantwortlichkeiten:
 *  - alles in genau EINE DB-Transaktion klammern (Atomarität),
 *  - die Geschäftslogik über eine Pipeline abarbeiten,
 *  - persistieren,
 *  - Nebeneffekte per Event auslösen (nicht selbst erledigen!).
 */
final class CreateOrderAction
{
    public function __construct(private readonly Pipeline $pipeline)
    {
    }

    public function execute(CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $context = $this->pipeline
                ->send(new CheckoutContext($data))
                ->through([
                    CheckStockPipe::class,
                    ApplyDiscountsPipe::class,
                    CalculateTaxPipe::class,
                ])
                ->thenReturn();

            $order = Order::create([
                'tenant_id' => $data->tenantId,
                'customer_email' => $data->customerEmail,
                'voucher_code' => $data->voucherCode,
                'status' => 'pending',
                'subtotal_cents' => $context->subtotalCents,
                'discount_cents' => $context->discountCents,
                'tax_cents' => $context->taxCents,
                'total_cents' => $context->totalCents(),
            ]);

            foreach ($data->items as $item) {
                $product = $context->products->get($item->productId);

                $order->items()->create([
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $product->price_cents,
                ]);

                // Bestand innerhalb der Transaktion reduzieren.
                $product->decrement('stock', $item->quantity);
            }

            // Nebeneffekt entkoppeln: der Listener kümmert sich um die Bestätigung.
            OrderCreated::dispatch($order);

            return $order;
        });
    }
}
