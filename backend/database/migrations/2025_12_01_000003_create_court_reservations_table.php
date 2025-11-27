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
        Schema::create('court_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('court_number')->comment('Court number from 1 to number_of_courts');
            $table->date('reservation_date');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_minutes');
            $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED'])->default('CONFIRMED');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            // Composite index for conflict checking (using shorter name to avoid MySQL 64 char limit)
            $table->index(['reservation_date', 'start_time', 'court_number'], 'court_res_date_time_court_idx');
            // Index for member reservation queries
            $table->index('user_id', 'court_res_user_id_idx');
            // Index for status filtering
            $table->index('status', 'court_res_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_reservations');
    }
};

