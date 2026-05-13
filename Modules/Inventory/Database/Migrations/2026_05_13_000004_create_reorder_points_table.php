<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReorderPointsTable extends Migration
{
    public function up()
    {
        Schema::create('reorder_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('variation_key')->nullable();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('module_id')->index();
            $table->decimal('reorder_at', 10, 2)->default(0);
            $table->decimal('reorder_qty', 10, 2)->default(0);
            $table->unsignedBigInteger('preferred_supplier_id')->nullable();
            $table->tinyInteger('auto_notify')->default(1);
            $table->timestamps();

            $table->unique(['item_id', 'store_id', 'variation_key'], 'reorder_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reorder_points');
    }
}
