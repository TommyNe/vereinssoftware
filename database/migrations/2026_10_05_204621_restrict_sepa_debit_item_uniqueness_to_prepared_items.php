<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sepa_debit_items', function (Blueprint $table): void {
            $table->dropUnique(['contribution_charge_id']);
        });

        DB::statement("CREATE UNIQUE INDEX sepa_debit_items_prepared_charge_unique ON sepa_debit_items (contribution_charge_id) WHERE status = 'prepared'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sepa_debit_items', function (Blueprint $table): void {
            $table->dropIndex('sepa_debit_items_prepared_charge_unique');
            $table->unique('contribution_charge_id');
        });
    }
};
