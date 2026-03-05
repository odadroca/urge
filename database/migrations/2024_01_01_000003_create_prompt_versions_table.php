<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('prompts')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('content');
            $table->string('commit_message', 500)->nullable();
            $table->json('variables')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->nullable();

            $table->unique(['prompt_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_versions');
    }
};
