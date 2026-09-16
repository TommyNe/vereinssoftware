<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('club_id')
                ->constrained('clubs')
                ->cascadeOnDelete();

            $table->string('email');

            $table->string('role', 100);

            $table->string('token_hash', 64)
                ->unique();

            $table->foreignId('invited_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('expires_at');

            $table->timestamp('accepted_at')
                ->nullable();

            $table->timestamp('revoked_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'club_id',
                'email',
            ]);

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'club_invitations'
        );
    }
};
