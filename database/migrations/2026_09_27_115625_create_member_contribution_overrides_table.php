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
            'member_contribution_overrides',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->uuid('club_id');
                $table->uuid('member_id');

                $table->uuid(
                    'contribution_type_id'
                );

                $table->string(
                    'type',
                    32
                );

                $table->decimal(
                    'amount',
                    10,
                    2
                )->nullable();

                $table->date(
                    'valid_from'
                );

                $table->date(
                    'valid_until'
                )->nullable();

                $table->string(
                    'reason',
                    500
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

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

                $table->foreign('member_id')
                    ->references('uuid')
                    ->on('members')
                    ->cascadeOnDelete();

                $table->foreign(
                    'contribution_type_id'
                )
                    ->references('id')
                    ->on('contribution_types')
                    ->cascadeOnDelete();

                $table->index([
                    'club_id',
                    'member_id',
                    'contribution_type_id',
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_contribution_overrides');
    }
};
