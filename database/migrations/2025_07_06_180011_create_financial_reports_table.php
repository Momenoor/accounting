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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', [
                'balance_sheet',
                'income_statement',
                'cash_flow',
                'trial_balance',
                'custom'
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('fiscal_year_id')->constrained();
            $table->json('report_data')->nullable(); // For storing generated report data
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('financial_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('financial_reports');
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->string('item_type'); // header, account, total, etc.
            $table->integer('sort_order')->default(0);
            $table->foreignId('account_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
