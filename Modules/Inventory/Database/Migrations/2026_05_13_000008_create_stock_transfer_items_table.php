<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockTransferItemsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('variation_key')->nullable();
            $table->decimal('qty_requested', 10, 2)->default(0);
            $table->decimal('qty_transferred', 10, 2)->default(0);
            $table->decimal('qty_received', 10, 2)->default(0);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_transfer_items');
    }
}
