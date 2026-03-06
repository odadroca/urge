<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptEnvironment extends Model
{
    protected $fillable = [
        'prompt_id',
        'name',
        'prompt_version_id',
    ];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    public function version()
    {
        return $this->belongsTo(PromptVersion::class, 'prompt_version_id');
    }
}
