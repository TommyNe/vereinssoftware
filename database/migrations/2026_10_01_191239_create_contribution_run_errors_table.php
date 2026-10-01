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
            'contribution_run_errors',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid(
                    'contribution_run_id'
                );

                $table->uuid(
                    'member_id'
                )->nullable();

                $table->string(
                    'message',
                    1000
                );

                $table->timestamps();

                $table->foreign(
                    'contribution_run_id'
                )
                    ->references('id')
                    ->on('contribution_runs')
                    ->cascadeOnDelete();

                $table->foreign(
                    'member_id'
                )
                    ->references('uuid')
                    ->on('members')
                    ->nullOnDelete();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_run_errors');
    }
};
