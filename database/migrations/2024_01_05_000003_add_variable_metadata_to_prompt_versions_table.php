<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompt_versions', function (Blueprint $table) {
            $table->json('variable_metadata')->nullable()->after('variables');
        });
    }

    public function down(): void
    {
        Schema::table('prompt_versions', function (Blueprint $table) {
            $table->dropColumn('variable_metadata');
        });
    }
};
