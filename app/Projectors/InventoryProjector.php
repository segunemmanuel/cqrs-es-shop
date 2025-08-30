<?php

namespace App\Projectors;

use Illuminate\Support\Facades\DB;

class InventoryProjector
{
    public function onInventoryAdjusted(array $p): void
    {
        DB::table('inventory_reads')->updateOrInsert(
            ['product_id' => $p['product_id']],
            [
                'on_hand'    => DB::raw('COALESCE(on_hand,0) + '.(int)$p['delta']),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
