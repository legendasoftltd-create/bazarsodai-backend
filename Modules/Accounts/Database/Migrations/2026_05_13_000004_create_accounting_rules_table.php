<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingRulesTable extends Migration
{
    public function up()
    {
        Schema::create('accounting_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100)->unique();  // order_completed, refund, cod_collected ...
            $table->json('lines');  // [{account_code, side, amount_field, fixed_amount}, ...]
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounting_rules');
    }
}
