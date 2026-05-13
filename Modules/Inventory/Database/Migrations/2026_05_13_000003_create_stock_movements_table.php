<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockMovementsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('variation_key')->nullable();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('module_id')->index();
            $table->enum('type', [
                'opening',
                'purchase',
                'purchase_return',
                'sale',
                'sale_return',
                'damaged',
                'broken',
                'internal_use',
                'adjustment_add',
                'adjustment_sub',
                'transfer_in',
                'transfer_out',
            ]);
            $table->decimal('qty_in', 10, 2)->default(0);
            $table->decimal('qty_out', 10, 2)->default(0);
            $table->decimal('stock_before', 10, 2)->default(0);
            $table->decimal('stock_after', 10, 2)->default(0);
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('average');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'store_id', 'type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
}
