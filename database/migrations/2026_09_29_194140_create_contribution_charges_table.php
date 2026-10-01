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
            'contribution_charges',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');
                $table->uuid('member_id');
                $table->uuid('contribution_type_id');

                $table->string(
                    'status',
                    32
                );

                $table->decimal(
                    'amount',
                    10,
                    2
                );

                $table->string(
                    'description'
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

                $table->timestamp(
                    'paid_at'
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

                $table->foreign('member_id')
                    ->references('uuid')
                    ->on('members')
                    ->cascadeOnDelete();

                $table->foreign(
                    'contribution_type_id'
                )
                    ->references('id')
                    ->on('contribution_types')
                    ->restrictOnDelete();

                $table->index([
                    'club_id',
                    'member_id',
                    'status',
                ]);

                $table->index([
                    'club_id',
                    'due_date',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_charges');
    }
};
