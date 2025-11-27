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
        Schema::create('facility_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('number_of_courts')->default(1);
            $table->integer('minimum_reservation_duration_minutes')->default(30)->comment('Options: 30, 60, 90, 120, etc.');
            $table->integer('advance_booking_days')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_settings');
    }
};

