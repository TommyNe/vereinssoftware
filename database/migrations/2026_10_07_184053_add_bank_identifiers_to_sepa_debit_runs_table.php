<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'sepa_debit_runs',
            function (Blueprint $table): void {
                $table->string(
                    'message_id',
                    35
                )
                    ->nullable()
                    ->unique();

                $table->string(
                    'payment_information_id',
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
            'sepa_debit_runs',
            function (Blueprint $table): void {
                $table->dropUnique([
                    'message_id',
                ]);

                $table->dropUnique([
                    'payment_information_id',
                ]);

                $table->dropColumn([
                    'message_id',
                    'payment_information_id',
                ]);
            }
        );
    }
};
