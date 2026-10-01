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
        Schema::table(
            'contribution_charges',
            function (Blueprint $table): void {
                $table->uuid(
                    'contribution_run_id'
                )
                    ->nullable()
                    ->after(
                        'contribution_type_id'
                    );

                $table->foreign(
                    'contribution_run_id'
                )
                    ->references('id')
                    ->on('contribution_runs')
                    ->nullOnDelete();

                $table->index(
                    'contribution_run_id'
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contribution_charges', function (Blueprint $table) {
            //
        });
    }
};
