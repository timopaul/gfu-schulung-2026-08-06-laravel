<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\OrderBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_email',
        'voucher_code',
        'status',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /**
     * Block 3: Ein eigener Query-Builder gibt lesbare, wiederverwendbare Scopes.
     */
    public function newEloquentBuilder($query): OrderBuilder
    {
        return new OrderBuilder($query);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
