<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'member_documents',
            function (
                Blueprint $table
            ): void {
                $table->uuid('id')
                    ->primary();

                $table->uuid('club_id');

                $table->uuid('member_id');

                $table->string(
                    'type',
                    100
                );

                $table->string(
                    'original_name'
                );

                $table->string(
                    'storage_path'
                );

                $table->string(
                    'mime_type',
                    150
                );

                $table->unsignedBigInteger(
                    'size'
                );

                $table->string(
                    'checksum',
                    128
                )
                    ->nullable();

                $table->foreignId(
                    'uploaded_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->foreign(
                    'club_id'
                )
                    ->references('id')
                    ->on('clubs')
                    ->cascadeOnDelete();

                $table->foreign(
                    'member_id'
                )
                    ->references('id')
                    ->on('members')
                    ->cascadeOnDelete();

                $table->index([
                    'club_id',
                    'member_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'member_documents'
        );
    }
};
