<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'club_sepa_configurations',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');

                $table->string(
                    'creditor_identifier',
                    100
                );

                $table->string(
                    'account_holder'
                );

                $table->text('iban');

                $table->text('bic')
                    ->nullable();

                $table->string(
                    'mandate_reference_prefix',
                    30
                )
                    ->nullable();

                $table->unsignedSmallInteger(
                    'default_lead_days'
                )
                    ->default(5);

                $table->string(
                    'default_purpose',
                    140
                )
                    ->nullable();

                $table->boolean(
                    'is_active'
                )
                    ->default(true);

                $table->foreignId(
                    'created_by'
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

                /*
                 * Pro Verein genau eine
                 * SEPA-Konfiguration.
                 */
                $table->unique(
                    'club_id'
                );

                /*
                 * Die Gläubiger-ID sollte nicht
                 * innerhalb derselben Anwendung
                 * doppelt vorkommen.
                 */
                $table->unique(
                    'creditor_identifier'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'club_sepa_configurations'
        );
    }
};
