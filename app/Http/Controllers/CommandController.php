<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CommandController extends Controller
{
    public function createProduct(Request $req)
{
    $id = \Illuminate\Support\Str::uuid()->toString();
    $version = $this->nextVersion($id);

    DB::table('event_store')->insert([
        'aggregate_id'   => $id,
        'aggregate_type' => 'product',
        'version'        => $version,
        'event_name'     => 'ProductCreated',
        'payload'        => json_encode([
            'id'          => $id,
            'sku'         => (string) $req->sku,
            'name'        => (string) $req->name,
            'price_cents' => (int) $req->price_cents,
            'description' => $req->description,
        ]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('ProductCreated', [
        'id'          => $id,
        'sku'         => (string) $req->sku,
        'name'        => (string) $req->name,
        'price_cents' => (int) $req->price_cents,
        'description' => $req->description,
    ]);

    return response()->json(['product_id' => $id], 201);
}



public function adjustInventory(Request $req)
{
    $productId = (string) $req->product_id;
    $version   = $this->nextVersion($productId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $productId,
        'aggregate_type' => 'product', // or 'inventory' if you model it separately
        'version'        => $version,
        'event_name'     => 'InventoryAdjusted',
        'payload'        => json_encode([
            'product_id' => $productId,
            'delta'      => (int) $req->delta,
        ]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('InventoryAdjusted', [
        'product_id' => $productId,
        'delta'      => (int) $req->delta,
    ]);

    return response()->json(['status' => 'ok']);
}


public function createOrder()
{
    $orderId = \Illuminate\Support\Str::uuid()->toString();
    $version = $this->nextVersion($orderId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $orderId,
        'aggregate_type' => 'order',
        'version'        => $version,
        'event_name'     => 'OrderCreated',
        'payload'        => json_encode(['order_id' => $orderId]),
        'meta'           => json_encode([]),
        'occurred_at'    => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('OrderCreated', [
        'order_id' => $orderId,
    ]);

    return response()->json(['order_id' => $orderId], 201);
}

public function addItemToOrder($orderId, Request $req)
{
    $version = $this->nextVersion($orderId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $orderId,
        'aggregate_type' => 'order',
        'version'        => $version,
        'event_name'     => 'OrderItemAdded',
        'payload'        => json_encode([
            'order_id'    => (string) $orderId,
            'product_id'  => (string) $req->product_id,
            'qty'         => (int) $req->qty,
            'price_cents' => (int) $req->price_cents,
        ]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('OrderItemAdded', [
        'order_id'    => (string) $orderId,
        'product_id'  => (string) $req->product_id,
        'qty'         => (int) $req->qty,
        'price_cents' => (int) $req->price_cents,
    ]);

    return response()->json(['status' => 'ok', 'order_id' => $orderId]);
}

public function placeOrder($orderId)
{
    $version = $this->nextVersion($orderId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $orderId,
        'aggregate_type' => 'order',
        'version'        => $version,
        'event_name'     => 'OrderPlaced',
        'payload'        => json_encode(['order_id' => (string) $orderId]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('OrderPlaced', [
        'order_id' => (string) $orderId,
    ]);

    return response()->json(['status' => 'placed', 'order_id' => $orderId]);
}

public function cancelOrder($orderId)
{
    $version = $this->nextVersion($orderId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $orderId,
        'aggregate_type' => 'order',
        'version'        => $version,
        'event_name'     => 'OrderCancelled',
        'payload'        => json_encode(['order_id' => (string) $orderId]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('OrderCancelled', [
        'order_id' => (string) $orderId,
    ]);

    return response()->json(['status' => 'cancelled', 'order_id' => $orderId]);
}




    private function nextVersion(string $aggregateId): int
{
    $v = DB::table('event_store')
        ->where('aggregate_id', $aggregateId)
        ->max('version');

    return ((int) $v) + 1;
}


    public function removeItemFromOrder(string $orderId, \Illuminate\Http\Request $req)
{
    $data = $req->validate([
        'product_id' => 'required|uuid',
        'qty'        => 'required|integer|min:1',
        'expected_version' => 'sometimes|integer|min:1', // for optimistic concurrency
    ]);

    // optimistic concurrency (optional)
    if (isset($data['expected_version'])) {
        $current = (int) DB::table('event_store')->where('aggregate_id', $orderId)->max('version');
        if ($current !== (int) $data['expected_version']) {
            return response()->json([
                'error' => 'version_conflict',
                'message' => "Expected version {$data['expected_version']}, current is {$current}"
            ], 409);
        }
    }

    $version = $this->nextVersion($orderId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $orderId,
        'aggregate_type' => 'order',
        'version'        => $version,
        'event_name'     => 'OrderItemRemoved',
        'payload'        => json_encode([
            'order_id'   => (string) $orderId,
            'product_id' => (string) $data['product_id'],
            'qty'        => (int) $data['qty'],
        ]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('OrderItemRemoved', [
        'order_id'   => (string) $orderId,
        'product_id' => (string) $data['product_id'],
        'qty'        => (int) $data['qty'],
    ]);

    return response()->json(['status' => 'ok', 'order_id' => $orderId]);
}

/**
 * POST /api/commands/orders/{orderId}/payment/authorize
 * Body: { "amount_cents": 2499 }
 */
public function markPaymentAuthorized(string $orderId, \Illuminate\Http\Request $req)
{
    $data = $req->validate([
        'amount_cents'    => 'required|integer|min:0',
        'expected_version'=> 'sometimes|integer|min:1', // optional optimistic concurrency
    ]);

    // optimistic concurrency (optional)
    if (isset($data['expected_version'])) {
        $current = (int) DB::table('event_store')->where('aggregate_id', $orderId)->max('version');
        if ($current !== (int) $data['expected_version']) {
            return response()->json([
                'error' => 'version_conflict',
                'message' => "Expected version {$data['expected_version']}, current is {$current}"
            ], 409);
        }
    }

    $version = $this->nextVersion($orderId);

    DB::table('event_store')->insert([
        'aggregate_id'   => $orderId,
        'aggregate_type' => 'order',
        'version'        => $version,
        'event_name'     => 'PaymentAuthorized',
        'payload'        => json_encode([
            'order_id'     => (string) $orderId,
            'amount_cents' => (int) $data['amount_cents'],
        ]),
        'meta'        => json_encode([]),
        'occurred_at' => now(),
    ]);

    app(\App\Projectors\ProjectorRegistry::class)->dispatch('PaymentAuthorized', [
        'order_id'     => (string) $orderId,
        'amount_cents' => (int) $data['amount_cents'],
    ]);

    return response()->json(['status' => 'payment_authorized', 'order_id' => $orderId]);
}

}
