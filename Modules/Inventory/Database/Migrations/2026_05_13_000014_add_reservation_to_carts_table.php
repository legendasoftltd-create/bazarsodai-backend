<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReservationToCartsTable extends Migration
{
    public function up()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->timestamp('reserved_until')->nullable()->after('updated_at');
            $table->tinyInteger('stock_reserved')->default(0)->after('reserved_until');
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['reserved_until', 'stock_reserved']);
        });
    }
}
