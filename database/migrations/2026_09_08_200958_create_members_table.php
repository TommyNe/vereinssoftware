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
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('club_id')
                ->constrained('clubs')
                ->cascadeOnDelete();

            $table->string('member_number');

            $table->string('first_name');
            $table->string('last_name');

            $table->date('birth_date')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('status', 32)
                ->default('active');

            $table->date('joined_at');
            $table->date('left_at')->nullable();

            $table->timestamps();

            $table->unique([
                'club_id',
                'member_number',
            ]);

            $table->index([
                'club_id',
                'last_name',
                'first_name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
