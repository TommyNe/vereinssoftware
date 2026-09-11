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
        Schema::create('membership_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table
                ->foreignUuid('club_id')
                ->constrained('clubs')
                ->cascadeOnDelete();

            $table->string('name', 100);

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};
