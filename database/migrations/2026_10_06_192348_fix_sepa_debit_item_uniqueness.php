<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS sepa_debit_items_contribution_charge_id_unique'
        );

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX sepa_debit_items_active_charge_unique
            ON sepa_debit_items (contribution_charge_id)
            WHERE status = 'prepared'
        SQL);
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS sepa_debit_items_active_charge_unique'
        );

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX sepa_debit_items_contribution_charge_id_unique
            ON sepa_debit_items (contribution_charge_id)
        SQL);
    }
};
