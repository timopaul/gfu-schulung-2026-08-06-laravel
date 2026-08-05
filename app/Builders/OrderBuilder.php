<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Block 3 – Ein eigener Eloquent-Builder bündelt Query-Logik an EINER Stelle
 * und macht Aufrufe lesbar: Order::query()->forTenant(1)->paid()->withItemCount()
 *
 * @extends Builder<\App\Models\Order>
 */
class OrderBuilder extends Builder
{
    public function forTenant(int $tenantId): self
    {
        return $this->where('tenant_id', $tenantId);
    }

    public function paid(): self
    {
        return $this->where('status', 'paid');
    }

    public function pending(): self
    {
        return $this->where('status', 'pending');
    }

    /**
     * Subquery statt N+1: hängt eine berechnete Spalte items_count an,
     * ohne die Items nachzuladen.
     */
    public function withItemCount(): self
    {
        return $this->withCount('items');
    }

    public function highValue(int $thresholdCents = 100_00): self
    {
        return $this->where('total_cents', '>=', $thresholdCents);
    }
}
