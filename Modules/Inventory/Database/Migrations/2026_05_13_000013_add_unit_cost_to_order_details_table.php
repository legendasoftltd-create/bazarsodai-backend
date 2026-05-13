<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnitCostToOrderDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('unit_cost_at_sale', 15, 2)->default(0)->after('price');
            $table->unsignedBigInteger('batch_id')->nullable()->after('unit_cost_at_sale');
        });
    }

    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_at_sale', 'batch_id']);
        });
    }
}
