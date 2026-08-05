<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $acme = Tenant::factory()->create([
            'name' => 'ACME GmbH',
            'api_key' => 'key_acme_demo',
        ]);

        $globex = Tenant::factory()->create([
            'name' => 'Globex AG',
            'api_key' => 'key_globex_demo',
        ]);

        Product::factory()->create([
            'tenant_id' => $acme->id,
            'name' => 'Mechanische Tastatur',
            'sku' => 'ACME-KB-01',
            'price_cents' => 12900,
            'stock' => 25,
        ]);

        Product::factory()->create([
            'tenant_id' => $acme->id,
            'name' => 'USB-C Dockingstation',
            'sku' => 'ACME-DOCK-01',
            'price_cents' => 19900,
            'stock' => 5,
        ]);

        Product::factory()->create([
            'tenant_id' => $globex->id,
            'name' => 'Ergonomische Maus',
            'sku' => 'GLBX-MOUSE-01',
            'price_cents' => 5900,
            'stock' => 0, // bewusst ausverkauft für die CheckStockPipe-Übung
        ]);

        Voucher::factory()->create([
            'code' => 'WELCOME10',
            'percent_off' => 10,
            'active' => true,
        ]);

        Voucher::factory()->expired()->create([
            'code' => 'EXPIRED20',
            'percent_off' => 20,
        ]);
    }
}
