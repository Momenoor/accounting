<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('cost', 15, 2);
            $table->integer('quantity');
            $table->foreignId('category_id')->constrained('product_categories');
            $table->foreignId('inventory_account_id')->constrained('accounts');
            $table->foreignId('cogs_account_id')->constrained('accounts');
            $table->decimal('last_purchase_cost')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
