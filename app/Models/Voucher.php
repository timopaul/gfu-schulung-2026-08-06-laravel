<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'percent_off', 'expires_at', 'active'];

    protected function casts(): array
    {
        return [
            'percent_off' => 'integer',
            'expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function isRedeemable(): bool
    {
        return $this->active
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
