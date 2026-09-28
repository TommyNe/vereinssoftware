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
        Schema::create('contribution_rates', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('club_id');

            $table->uuid(
                'contribution_type_id'
            );

            $table->uuid(
                'membership_type_id'
            )->nullable();

            $table->decimal(
                'amount',
                10,
                2
            );

            $table->date(
                'valid_from'
            );

            $table->date(
                'valid_until'
            )->nullable();

            $table->boolean(
                'is_active'
            )->default(true);

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
                ->cascadeOnDelete();

            $table->foreign(
                'membership_type_id'
            )
                ->references('id')
                ->on('membership_types')
                ->nullOnDelete();

            $table->index([
                'club_id',
                'contribution_type_id',
                'membership_type_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_rates');
    }
};
