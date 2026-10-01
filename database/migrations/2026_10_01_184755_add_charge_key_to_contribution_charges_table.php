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
        Schema::table(
            'contribution_charges',
            function (Blueprint $table): void {
                $table->string(
                    'charge_key',
                    190
                )
                    ->nullable()
                    ->after(
                        'description'
                    );
                $table->unique(
                    [
                        'club_id',
                        'member_id',
                        'contribution_type_id',
                        'charge_key',
                    ],
                    'contribution_charges_unique_period'
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contribution_charges', function (Blueprint $table) {
            $table->dropUnique('contribution_charges_unique_period');
            $table->dropColumn('charge_key');
        });
    }
};
