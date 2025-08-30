<?php

namespace App\Projectors;

use Illuminate\Support\Facades\DB;

class ProductProjector
{
    public function onProductCreated(array $p): void
    {
        DB::table('product_reads')->updateOrInsert(
            ['id' => $p['id']],
            [
                'sku'         => $p['sku'],
                'name'        => $p['name'],
                'description' => $p['description'] ?? null,
                'price_cents' => (int) $p['price_cents'],
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
    }
}
