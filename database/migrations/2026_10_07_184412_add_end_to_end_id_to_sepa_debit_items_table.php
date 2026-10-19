<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'sepa_debit_items',
            function (Blueprint $table): void {
                $table->string(
                    'end_to_end_id',
                    35
                )
                    ->nullable()
                    ->unique();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'sepa_debit_items',
            function (Blueprint $table): void {
                $table->dropUnique([
                    'end_to_end_id',
                ]);

                $table->dropColumn(
                    'end_to_end_id'
                );
            }
        );
    }
};
