<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained();
            $table->date('transaction_date');
            $table->string('description');
            $table->string('reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['deposit', 'withdrawal', 'fee', 'interest']);
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
