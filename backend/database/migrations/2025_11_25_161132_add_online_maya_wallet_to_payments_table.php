<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add ONLINE_MAYA_WALLET to the payment_method enum.
     */
    public function up(): void
    {
        // Modify the enum to include ONLINE_MAYA_WALLET
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('CASH', 'ONLINE_MAYA', 'ONLINE_CARD', 'ONLINE_MAYA_WALLET') NOT NULL");
    }

    /**
     * Reverse the migrations.
     * Remove ONLINE_MAYA_WALLET from the payment_method enum.
     */
    public function down(): void
    {
        // Before removing, update any ONLINE_MAYA_WALLET payments to ONLINE_MAYA
        DB::table('payments')
            ->where('payment_method', 'ONLINE_MAYA_WALLET')
            ->update(['payment_method' => 'ONLINE_MAYA']);

        // Modify the enum to remove ONLINE_MAYA_WALLET
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('CASH', 'ONLINE_MAYA', 'ONLINE_CARD') NOT NULL");
    }
};
