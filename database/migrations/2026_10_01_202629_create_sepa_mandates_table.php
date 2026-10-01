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
            'sepa_mandates',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');
                $table->uuid('member_id');

                $table->string(
                    'status',
                    32
                );

                $table->string(
                    'mandate_reference',
                    100
                );

                $table->string(
                    'account_holder'
                );

                /*
                 * Verschlüsselte Werte brauchen TEXT,
                 * da Ciphertext deutlich länger sein kann.
                 */
                $table->text('iban');

                $table->text('bic')
                    ->nullable();

                $table->date(
                    'signed_at'
                );

                $table->date(
                    'valid_from'
                )->nullable();

                $table->timestamp(
                    'revoked_at'
                )->nullable();

                $table->text(
                    'revocation_reason'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'revoked_by'
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
                    ->cascadeOnDelete();

                $table->unique([
                    'club_id',
                    'mandate_reference',
                ]);

                $table->index([
                    'club_id',
                    'member_id',
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
        Schema::dropIfExists('sepa_mandates');
    }
};
