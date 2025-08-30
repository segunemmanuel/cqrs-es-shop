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
         Schema::create('product_reads', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('sku')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->integer('price_cents');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reads');
    }
};
