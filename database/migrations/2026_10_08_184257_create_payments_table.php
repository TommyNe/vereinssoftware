<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'payments',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');
                $table->uuid('member_id');

                $table->string(
                    'status',
                    32
                );

                $table->string(
                    'method',
                    50
                );

                $table->decimal(
                    'amount',
                    12,
                    2
                );

                $table->char(
                    'currency',
                    3
                )->default('EUR');

                /*
                 * Datum, an dem die Zahlung
                 * tatsächlich verbucht wurde.
                 */
                $table->date(
                    'booking_date'
                );

                /*
                 * Wertstellung der Bank.
                 * Kann von booking_date abweichen.
                 */
                $table->date(
                    'value_date'
                )->nullable();

                $table->string(
                    'reference',
                    255
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                /*
                 * Quelle für idempotente
                 * automatische Buchungen.
                 *
                 * Beispiel:
                 * source_type = sepa_debit_item
                 * source_id   = UUID des Items
                 */
                $table->string(
                    'source_type',
                    100
                )->nullable();

                $table->uuid(
                    'source_id'
                )->nullable();

                $table->timestamp(
                    'reversed_at'
                )->nullable();

                $table->text(
                    'reversal_reason'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'reversed_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->foreign('club_id')
                    ->references('id')
                    ->on('clubs')
                    ->cascadeOnDelete();

                $table->foreign('member_id')
                    ->references('uuid')
                    ->on('members')
                    ->restrictOnDelete();

                $table->index([
                    'club_id',
                    'member_id',
                    'booking_date',
                ]);

                $table->index([
                    'club_id',
                    'status',
                ]);

                $table->unique(
                    [
                        'club_id',
                        'source_type',
                        'source_id',
                    ],
                    'payments_unique_source'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'payments'
        );
    }
};
