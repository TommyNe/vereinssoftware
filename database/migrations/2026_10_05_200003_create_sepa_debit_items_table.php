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
            'sepa_debit_items',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');
                $table->uuid('sepa_debit_run_id');
                $table->uuid('contribution_charge_id');
                $table->uuid('member_id');
                $table->uuid('sepa_mandate_id');

                $table->decimal(
                    'amount',
                    10,
                    2
                );

                $table->string(
                    'purpose',
                    140
                );

                /*
                 * Snapshots.
                 */
                $table->string(
                    'account_holder'
                );

                $table->text(
                    'iban'
                );

                $table->text(
                    'bic'
                )->nullable();

                $table->string(
                    'mandate_reference',
                    100
                );

                $table->date(
                    'mandate_signed_at'
                );

                $table->timestamps();

                $table->foreign('club_id')
                    ->references('id')
                    ->on('clubs')
                    ->cascadeOnDelete();

                $table->foreign('sepa_debit_run_id')
                    ->references('id')
                    ->on('sepa_debit_runs')
                    ->cascadeOnDelete();

                $table->foreign('contribution_charge_id')
                    ->references('id')
                    ->on('contribution_charges')
                    ->restrictOnDelete();

                $table->foreign('member_id')
                    ->references('uuid')
                    ->on('members')
                    ->restrictOnDelete();

                $table->foreign('sepa_mandate_id')
                    ->references('id')
                    ->on('sepa_mandates')
                    ->restrictOnDelete();

                $table->unique(
                    'contribution_charge_id'
                );

                $table->index([
                    'club_id',
                    'sepa_debit_run_id',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepa_debit_items');
    }
};
