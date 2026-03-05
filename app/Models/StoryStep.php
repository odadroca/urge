<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryStep extends Model
{
    protected $fillable = [
        'story_id',
        'sort_order',
        'prompt_id',
        'prompt_version_id',
        'library_entry_id',
        'notes',
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    public function version()
    {
        return $this->belongsTo(PromptVersion::class, 'prompt_version_id');
    }

    public function libraryEntry()
    {
        return $this->belongsTo(LibraryEntry::class, 'library_entry_id');
    }
}
