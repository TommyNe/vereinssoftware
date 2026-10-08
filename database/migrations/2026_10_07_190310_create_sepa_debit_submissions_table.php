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
            'sepa_debit_submissions',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');

                $table->uuid(
                    'sepa_debit_run_id'
                );

                $table->string(
                    'status',
                    32
                );

                $table->string(
                    'submission_method',
                    50
                );

                $table->string(
                    'bank_reference',
                    255
                )->nullable();

                $table->timestamp(
                    'submitted_at'
                );

                $table->timestamp(
                    'bank_responded_at'
                )->nullable();

                $table->text(
                    'bank_message'
                )->nullable();

                $table->foreignId(
                    'submitted_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->foreign('club_id')
                    ->references('id')
                    ->on('clubs')
                    ->cascadeOnDelete();

                $table->foreign(
                    'sepa_debit_run_id'
                )
                    ->references('id')
                    ->on('sepa_debit_runs')
                    ->restrictOnDelete();

                $table->unique(
                    'sepa_debit_run_id'
                );

                $table->index([
                    'club_id',
                    'status',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepa_debit_submissions');
    }
};
