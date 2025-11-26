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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('maya_checkout_id')->nullable()->after('payment_code');
            $table->string('maya_payment_id')->nullable()->after('maya_checkout_id');
            $table->string('maya_payment_token')->nullable()->after('maya_payment_id');
            $table->json('maya_metadata')->nullable()->after('maya_payment_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['maya_checkout_id', 'maya_payment_id', 'maya_payment_token', 'maya_metadata']);
        });
    }
};
