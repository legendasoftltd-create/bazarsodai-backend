<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('module_id')->index();
            $table->enum('status', ['draft', 'ordered', 'partial', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_qty', 10, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->timestamp('ordered_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}
