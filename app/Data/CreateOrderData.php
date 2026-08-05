<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\CreateOrderRequest;

/**
 * Block 1 – Zentrales Domain-DTO für die Bestellanlage.
 *
 * Wichtig: Der Controller/die Action arbeiten NIE mit $request->all(),
 * sondern ausschließlich mit diesem typisierten Objekt. Der FormRequest
 * validiert, das DTO transportiert – klare Trennung der Zuständigkeiten.
 *
 * @property-read array<int, OrderItemData> $items
 */
final readonly class CreateOrderData
{
    /** @param array<int, OrderItemData> $items */
    public function __construct(
        public int $tenantId,
        public string $customerEmail,
        public array $items,
        public ?string $voucherCode = null,
    ) {
    }

    /**
     * Fabrikmethode: baut das DTO aus dem bereits validierten FormRequest.
     * So bleibt die Validierungslogik im Request und die Domain sauber.
     */
    public static function fromRequest(CreateOrderRequest $request): self
    {
        $items = array_map(
            static fn (array $row): OrderItemData => OrderItemData::fromArray($row),
            $request->validated('items'),
        );

        return new self(
            tenantId: (int) $request->attributes->get('tenant_id'),
            customerEmail: (string) $request->validated('customer_email'),
            items: $items,
            voucherCode: $request->validated('voucher_code'),
        );
    }
}
