<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepa_debit_runs', function (Blueprint $table): void {
            $table->string('xml_format', 50)->nullable();

            $table->string('xml_storage_path')->nullable();

            $table->string('xml_sha256', 64)->nullable();

            $table->timestamp('xml_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sepa_debit_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'xml_format',
                'xml_storage_path',
                'xml_sha256',
                'xml_generated_at',
            ]);
        });
    }
};
