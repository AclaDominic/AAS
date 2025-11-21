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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('membership_offer_id')->constrained('membership_offers')->onDelete('cascade');
            $table->foreignId('promo_id')->nullable()->constrained('promos')->onDelete('set null');
            $table->foreignId('first_time_discount_id')->nullable()->constrained('first_time_discounts')->onDelete('set null');
            $table->string('payment_code', 8)->unique()->nullable();
            $table->enum('payment_method', ['CASH', 'ONLINE_MAYA', 'ONLINE_CARD']);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['PENDING', 'PAID', 'CANCELLED', 'FAILED'])->default('PENDING');
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
