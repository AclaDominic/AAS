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
        Schema::table('court_reservations', function (Blueprint $table) {
            $table->enum('category', ['GYM', 'BADMINTON_COURT'])->default('BADMINTON_COURT')->after('user_id');
            $table->index('category', 'court_res_category_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('court_reservations', function (Blueprint $table) {
            $table->dropIndex('court_res_category_idx');
            $table->dropColumn('category');
        });
    }
};
