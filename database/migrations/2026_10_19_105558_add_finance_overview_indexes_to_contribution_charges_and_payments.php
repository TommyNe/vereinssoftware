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
        Schema::table('contribution_charges', function (Blueprint $table): void {
            $table->index(['club_id', 'status', 'due_date']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['club_id', 'status', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['club_id', 'status', 'booking_date']);
        });

        Schema::table('contribution_charges', function (Blueprint $table): void {
            $table->dropIndex(['club_id', 'status', 'due_date']);
        });
    }
};
