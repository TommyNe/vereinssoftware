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
        Schema::create('club_functions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table
                ->foreignUuid('club_id')
                ->constrained('clubs')
                ->cascadeOnDelete();

            $table->string('name', 150);

            $table
                ->string('description')
                ->nullable();

            $table
                ->unsignedInteger('sort_order')
                ->default(0);

            $table
                ->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'club_id',
                'name',
            ]);

            $table->index([
                'club_id',
                'is_active',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_functions');
    }
};
