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
            'sepa_debit_item_events',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');

                $table->uuid(
                    'sepa_debit_item_id'
                );

                $table->string(
                    'type',
                    32
                );

                $table->timestamp(
                    'occurred_at'
                );

                $table->string(
                    'reason_code',
                    20
                )->nullable();

                $table->string(
                    'reason_text',
                    500
                )->nullable();

                $table->string(
                    'bank_reference',
                    255
                )->nullable();

                $table->string(
                    'source',
                    50
                )->default('manual');

                $table->foreignId(
                    'recorded_by'
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
                    'sepa_debit_item_id'
                )
                    ->references('id')
                    ->on('sepa_debit_items')
                    ->restrictOnDelete();

                $table->index([
                    'club_id',
                    'type',
                ]);

                $table->index([
                    'sepa_debit_item_id',
                    'occurred_at',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepa_debit_item_events');
    }
};
