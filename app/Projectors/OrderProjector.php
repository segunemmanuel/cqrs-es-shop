<?php

namespace App\Projectors;

use Illuminate\Support\Facades\DB;

class OrderProjector
{
    public function onOrderCreated(array $p): void
    {
        DB::table('order_reads')->updateOrInsert(
            ['id' => $p['order_id']],
            [
                'status'      => 'draft',
                'total_cents' => 0,
                'items'       => json_encode([]),
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
    }

    public function onOrderItemAdded(array $p): void
    {
        $row   = DB::table('order_reads')->where('id', $p['order_id'])->first();
        $items = $row ? json_decode($row->items, true) : [];

        $items[] = [
            'product_id'  => $p['product_id'],
            'qty'         => (int) $p['qty'],
            'price_cents' => (int) $p['price_cents'],
        ];

        $total = array_reduce($items, fn($c,$i) => $c + $i['qty']*$i['price_cents'], 0);

        DB::table('order_reads')->updateOrInsert(
            ['id' => $p['order_id']],
            [
                'items'       => json_encode($items),
                'total_cents' => $total,
                'status'      => $row->status ?? 'draft',
                'updated_at'  => now(),
                'created_at'  => $row->created_at ?? now(),
            ]
        );
    }

    public function onOrderPlaced(array $p): void
    {
        DB::table('order_reads')->where('id', $p['order_id'])->update([
            'status'     => 'placed',
            'updated_at' => now(),
        ]);
    }

    public function onOrderCancelled(array $p): void
    {
        DB::table('order_reads')->where('id', $p['order_id'])->update([
            'status'     => 'cancelled',
            'updated_at' => now(),
        ]);
    }
}
