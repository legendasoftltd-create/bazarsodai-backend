<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJournalEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 20)->unique();  // JE-000001
            $table->string('reference_type', 191)->nullable();  // Order, Refund, Disbursement ...
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('event_type', 100)->nullable();  // order_completed, refund, cod_collected ...
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->foreign('reversal_of_id')->references('id')->on('journal_entries')->onDelete('set null');
            $table->unsignedBigInteger('created_by')->nullable();  // admin id
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index('event_type');
            $table->index('status');
            $table->index('posted_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('journal_entries');
    }
}
