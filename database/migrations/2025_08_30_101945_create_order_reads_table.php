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
    Schema::create('order_reads', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('status');
            $t->integer('total_cents')->default(0);
            $t->json('items');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reads');
    }
};
