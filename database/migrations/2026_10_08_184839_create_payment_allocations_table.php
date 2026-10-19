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
            'payment_allocations',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');

                $table->uuid(
                    'payment_id'
                );

                $table->uuid(
                    'contribution_charge_id'
                );

                $table->decimal(
                    'amount',
                    12,
                    2
                );

                $table->timestamps();

                $table->foreign('club_id')
                    ->references('id')
                    ->on('clubs')
                    ->cascadeOnDelete();

                $table->foreign('payment_id')
                    ->references('id')
                    ->on('payments')
                    ->restrictOnDelete();

                $table->foreign(
                    'contribution_charge_id'
                )
                    ->references('id')
                    ->on('contribution_charges')
                    ->restrictOnDelete();

                $table->index([
                    'club_id',
                    'contribution_charge_id',
                ]);

                $table->unique([
                    'payment_id',
                    'contribution_charge_id',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
