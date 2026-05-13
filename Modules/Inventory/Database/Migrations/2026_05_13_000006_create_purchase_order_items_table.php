<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('variation_key')->nullable();
            $table->decimal('qty_ordered', 10, 2)->default(0);
            $table->decimal('qty_received', 10, 2)->default(0);
            $table->decimal('qty_returned', 10, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
}
