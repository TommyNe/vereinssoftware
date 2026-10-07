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
            'sepa_debit_runs',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');

                $table->string(
                    'status',
                    32
                );

                $table->string(
                    'name'
                );

                $table->date(
                    'collection_date'
                );

                $table->unsignedInteger(
                    'items_count'
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
                    'prepared_at'
                )->nullable();

                $table->timestamp(
                    'exported_at'
                )->nullable();

                $table->timestamp(
                    'cancelled_at'
                )->nullable();

                $table->text(
                    'cancellation_reason'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'cancelled_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->foreign('club_id')
                    ->references('id')
                    ->on('clubs')
                    ->cascadeOnDelete();

                $table->index([
                    'club_id',
                    'status',
                ]);

                $table->index([
                    'club_id',
                    'collection_date',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepa_debit_runs');
    }
};
