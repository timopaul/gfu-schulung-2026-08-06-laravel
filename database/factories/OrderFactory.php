<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_email' => $this->faker->safeEmail(),
            'voucher_code' => null,
            'status' => 'pending',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid']);
    }
}
