<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_prompt', function (Blueprint $table) {
            $table->foreignId('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->foreignId('prompt_id')->constrained('prompts')->cascadeOnDelete();
            $table->primary(['api_key_id', 'prompt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_prompt');
    }
};
