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
            'contribution_runs',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');

                $table->uuid(
                    'contribution_type_id'
                );

                $table->string(
                    'status',
                    50
                );

                $table->date(
                    'calculation_date'
                );

                $table->date(
                    'period_from'
                );

                $table->date(
                    'period_until'
                )->nullable();

                $table->date(
                    'due_date'
                );

                $table->string(
                    'description'
                );

                $table->unsignedInteger(
                    'members_processed'
                )->default(0);

                $table->unsignedInteger(
                    'charges_created'
                )->default(0);

                $table->unsignedInteger(
                    'members_exempt'
                )->default(0);

                $table->unsignedInteger(
                    'duplicates_skipped'
                )->default(0);

                $table->unsignedInteger(
                    'errors_count'
                )->default(0);

                $table->decimal(
                    'total_amount',
                    12,
                    2
                )->default('0.00');

                $table->timestamp(
                    'started_at'
                )->nullable();

                $table->timestamp(
                    'finished_at'
                )->nullable();

                $table->foreignId(
                    'created_by'
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
                    'contribution_type_id'
                )
                    ->references('id')
                    ->on('contribution_types')
                    ->restrictOnDelete();

                $table->index([
                    'club_id',
                    'status',
                ]);

                $table->index([
                    'club_id',
                    'calculation_date',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_runs');
    }
};
