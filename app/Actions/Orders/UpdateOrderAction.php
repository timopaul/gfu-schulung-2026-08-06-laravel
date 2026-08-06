<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Data\CreateOrderData;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Pipelines\Checkout\ApplyDiscountsPipe;
use App\Pipelines\Checkout\CalculateTaxPipe;
use App\Pipelines\Checkout\CheckoutContext;
use App\Pipelines\Checkout\CheckStockPipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

/**
 * Block 2 + Block 4 (Web-Erweiterung) – Use-Case "Bestellung ändern".
 *
 * Symmetrisch zur CreateOrderAction, aber mit einer zusätzlichen Feinheit:
 * Vor der Neuberechnung wird der Bestand der bisherigen Positionen
 * zurückgebucht, damit dieselbe Checkout-Pipeline (Bestand, Rabatt, Steuer)
 * unverändert wiederverwendet werden kann. Alles in EINER Transaktion.
 */
final class UpdateOrderAction
{
    public function __construct(private readonly Pipeline $pipeline)
    {
    }

    public function execute(Order $order, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $order->loadMissing('items');

            // 1. Bestand der alten Positionen zurückbuchen.
            foreach ($order->items as $old) {
                Product::query()
                    ->whereKey($old->product_id)
                    ->increment('stock', $old->quantity);
            }

            // 2. Geschäftslogik erneut durch dieselbe Pipeline – prüft jetzt
            //    gegen den zurückgebuchten Bestand.
            $context = $this->pipeline
                ->send(new CheckoutContext($data))
                ->through([
                    CheckStockPipe::class,
                    ApplyDiscountsPipe::class,
                    CalculateTaxPipe::class,
                ])
                ->thenReturn();

            // 3. Kopf + Summen aktualisieren.
            $order->update([
                'customer_email' => $data->customerEmail,
                'voucher_code' => $data->voucherCode,
                'subtotal_cents' => $context->subtotalCents,
                'discount_cents' => $context->discountCents,
                'tax_cents' => $context->taxCents,
                'total_cents' => $context->totalCents(),
            ]);

            // 4. Positionen ersetzen und neuen Bestand abbuchen.
            $order->items()->delete();

            foreach ($data->items as $item) {
                $product = $context->products->get($item->productId);

                $order->items()->create([
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $product->price_cents,
                ]);

                $product->decrement('stock', $item->quantity);
            }

            // Nebeneffekt entkoppeln – identisch zur Anlage, nur anderes Event.
            OrderUpdated::dispatch($order);

            return $order->refresh()->load('items');
        });
    }
}
