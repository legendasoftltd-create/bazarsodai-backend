<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInventoryColumnsToStoreConfigsTable extends Migration
{
    public function up()
    {
        Schema::table('store_configs', function (Blueprint $table) {
            $table->enum('inventory_valuation_method', ['fifo', 'lifo', 'average'])->default('average')->after('minimum_stock_for_warning');
            $table->integer('expiry_alert_days')->default(30)->after('inventory_valuation_method');
            $table->integer('stock_reservation_minutes')->default(15)->after('expiry_alert_days');
        });
    }

    public function down()
    {
        Schema::table('store_configs', function (Blueprint $table) {
            $table->dropColumn(['inventory_valuation_method', 'expiry_alert_days', 'stock_reservation_minutes']);
        });
    }
}
