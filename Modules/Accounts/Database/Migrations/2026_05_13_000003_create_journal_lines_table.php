<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJournalLinesTable extends Migration
{
    public function up()
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->decimal('debit', 23, 3)->default(0.000);
            $table->decimal('credit', 23, 3)->default(0.000);
            $table->string('description', 191)->nullable();
            // Dimensional filters — allow slicing the ledger by party
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('delivery_man_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('meta')->nullable();  // extra context (payment_method, gateway, etc.)
            $table->timestamps();

            $table->index(['account_id', 'journal_entry_id']);
            $table->index('store_id');
            $table->index('delivery_man_id');
            $table->index('order_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('journal_lines');
    }
}
