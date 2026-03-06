<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('prompts')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('prompt_version_id')->constrained('prompt_versions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['prompt_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_environments');
    }
};
