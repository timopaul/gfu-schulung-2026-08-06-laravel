<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper(Str::random(8)),
            'price_cents' => $this->faker->numberBetween(500, 20000),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
