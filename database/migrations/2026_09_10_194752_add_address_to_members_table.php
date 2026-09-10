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
        Schema::table('members', function (Blueprint $table) {
            $table->string('street')->nullable();
            $table->string('house_number', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)
                ->nullable()
                ->default('DE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['street', 'house_number', 'postal_code', 'city', 'country_code']);
        });
    }
};
