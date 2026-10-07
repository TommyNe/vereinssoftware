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
        Schema::create(
            'sepa_debit_run_errors',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid(
                    'sepa_debit_run_id'
                );

                $table->uuid(
                    'member_id'
                )->nullable();

                $table->uuid(
                    'contribution_charge_id'
                )->nullable();

                $table->string(
                    'message',
                    1000
                );

                $table->timestamps();

                $table->foreign(
                    'sepa_debit_run_id'
                )
                    ->references('id')
                    ->on('sepa_debit_runs')
                    ->cascadeOnDelete();

                $table->foreign(
                    'member_id'
                )
                    ->references('uuid')
                    ->on('members')
                    ->nullOnDelete();

                $table->foreign(
                    'contribution_charge_id'
                )
                    ->references('id')
                    ->on('contribution_charges')
                    ->nullOnDelete();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepa_debit_run_errors');
    }
};
