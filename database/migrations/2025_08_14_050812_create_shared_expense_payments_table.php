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
        Schema::create('shared_expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_expense_participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('paid_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->text('note')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['shared_expense_participant_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_expense_payments');
    }
};
