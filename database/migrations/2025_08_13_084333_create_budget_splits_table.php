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
        Schema::create('budget_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('share_amount', 10, 2); // How much this user should pay
            $table->decimal('paid_amount', 10, 2)->default(0); // How much this user has actually paid
            $table->enum('status', ['pending', 'partial', 'paid', 'overpaid'])->default('pending');
            $table->timestamps();

            $table->unique(['budget_item_id', 'user_id']);
            $table->index(['budget_item_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_splits');
    }
};
