<?php

use App\Models\BankAccount;
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
        Schema::create('direct_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Bill::class);
            $table->string('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained();
            $table->decimal('total', 15, 2);
            $table->foreignIdFor(\App\Models\Account::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_expenses');
    }
};
