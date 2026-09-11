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
        Schema::create('member_functions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table
                ->foreignUuid('member_id')
                ->constrained('members', 'uuid')
                ->cascadeOnDelete();

            $table
                ->foreignUuid('club_function_id')
                ->constrained('club_functions')
                ->restrictOnDelete();

            $table->date('valid_from');

            $table
                ->date('valid_until')
                ->nullable();

            $table->timestamps();

            $table->index([
                'member_id',
                'valid_until',
            ]);

            $table->index([
                'club_function_id',
                'valid_until',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_functions');
    }
};
