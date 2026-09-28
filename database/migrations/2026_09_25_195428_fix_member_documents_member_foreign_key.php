<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite must disable foreign keys outside a transaction to rebuild the table.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('member_documents', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')->references('uuid')->on('members')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('member_documents', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')->references('uuid')->on('members')->cascadeOnDelete();
        });
    }
};
