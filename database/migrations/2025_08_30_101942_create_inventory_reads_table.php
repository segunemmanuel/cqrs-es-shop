<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('inventory_reads', function (Blueprint $t) {
    $t->uuid('product_id')->primary();   // <-- this must exist
    $t->integer('on_hand')->default(0);
    $t->integer('reserved')->default(0);
    $t->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reads');
    }
};
