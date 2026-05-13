<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryAdjustmentItemsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adjustment_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('variation_key')->nullable();
            $table->decimal('system_qty', 10, 2)->default(0);
            $table->decimal('physical_qty', 10, 2)->default(0);
            $table->decimal('difference', 10, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();

            $table->foreign('adjustment_id')->references('id')->on('inventory_adjustments')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_adjustment_items');
    }
}
