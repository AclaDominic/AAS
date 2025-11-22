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
        Schema::create('billing_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('membership_subscription_id')->constrained('membership_subscriptions')->onDelete('cascade');
            $table->date('statement_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['PENDING', 'PAID', 'CANCELLED'])->default('PENDING');
            $table->date('due_date');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_statements');
    }
};

