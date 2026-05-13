<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('variation_key')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->decimal('qty_initial', 10, 2)->default(0);
            $table->decimal('qty_remaining', 10, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('average');
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['item_id', 'store_id', 'qty_remaining']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_batches');
    }
}
