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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_number');
            $table->year('pay_period_year');
            $table->tinyInteger('pay_period_month');
            $table->date('payment_date');
            $table->enum('status', ['draft', 'processed', 'paid', 'cancelled']);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('total_tax', 15, 2);
            $table->decimal('total_deductions', 15, 2);
            $table->decimal('net_pay', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
