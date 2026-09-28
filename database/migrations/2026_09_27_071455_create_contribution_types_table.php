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
        Schema::create('contribution_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('club_id');

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('interval', 32);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->foreign('club_id')
                ->references('id')
                ->on('clubs')
                ->cascadeOnDelete();

            $table->unique([
                'club_id',
                'name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_types');
    }
};
