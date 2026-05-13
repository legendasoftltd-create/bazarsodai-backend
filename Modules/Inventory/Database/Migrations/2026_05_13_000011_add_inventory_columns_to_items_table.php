<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInventoryColumnsToItemsTable extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('average_cost', 15, 2)->default(0)->after('stock');
            $table->decimal('total_stock_value', 15, 2)->default(0)->after('average_cost');
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->nullable()->after('total_stock_value');
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['average_cost', 'total_stock_value', 'valuation_method']);
        });
    }
}
