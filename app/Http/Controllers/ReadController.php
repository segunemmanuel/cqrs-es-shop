<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ReadController extends Controller
{
    public function listProducts()
    {
        return DB::table('product_reads')->paginate(10);
    }

    public function getProduct($id)
    {
        return DB::table('product_reads')->where('id', $id)->firstOrFail();
    }

    public function getInventory($productId)
    {
        return DB::table('inventory_reads')->where('product_id', $productId)->firstOrFail();
    }

    public function listOrders()
    {
        return DB::table('order_reads')->paginate(10);
    }

    public function getOrder($id)
    {
        return DB::table('order_reads')->where('id', $id)->firstOrFail();
    }
}
