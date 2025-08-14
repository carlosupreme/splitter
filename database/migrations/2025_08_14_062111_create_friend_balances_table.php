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
        Schema::create('friend_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance_amount', 10, 2)->default(0.00); // Positive = friend owes user, Negative = user owes friend
            $table->integer('total_expenses')->default(0);
            $table->decimal('total_user_created', 10, 2)->default(0.00); // Total of expenses user created with this friend
            $table->decimal('total_friend_created', 10, 2)->default(0.00); // Total of expenses friend created with user
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            // Ensure unique friendship pair (prevent duplicates)
            $table->unique(['user_id', 'friend_id']);

            // Index for faster queries
            $table->index(['user_id', 'friend_id']);
            $table->index(['balance_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friend_balances');
    }
};
