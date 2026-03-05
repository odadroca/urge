<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('llm_providers')->where('driver', 'openrouter')->exists();

        if (!$exists) {
            $maxSort = DB::table('llm_providers')->max('sort_order') ?? 0;

            DB::table('llm_providers')->insert([
                'driver'     => 'openrouter',
                'name'       => 'OpenRouter',
                'model'      => 'openai/gpt-4o-mini',
                'enabled'    => false,
                'sort_order' => $maxSort + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('llm_providers')->where('driver', 'openrouter')->delete();
    }
};
