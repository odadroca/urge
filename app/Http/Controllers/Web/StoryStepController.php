<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryStep;
use Illuminate\Http\Request;

class StoryStepController extends Controller
{
    public function store(Request $request, Story $story)
    {
        $validated = $request->validate([
            'prompt_id'         => ['required', 'exists:prompts,id'],
            'prompt_version_id' => ['required', 'exists:prompt_versions,id'],
            'library_entry_id'  => ['nullable', 'exists:library_entries,id'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $maxOrder = $story->steps()->max('sort_order') ?? -1;

        $story->steps()->create([
            ...$validated,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('stories.edit', $story)
            ->with('success', 'Step added.');
    }

    public function update(Request $request, Story $story, StoryStep $step)
    {
        $validated = $request->validate([
            'library_entry_id' => ['nullable', 'exists:library_entries,id'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $step->update($validated);

        return redirect()->route('stories.edit', $story)
            ->with('success', 'Step updated.');
    }

    public function destroy(Story $story, StoryStep $step)
    {
        $step->delete();

        // Re-pack sort_order to keep it gapless
        $story->steps()->orderBy('sort_order')->each(function ($s, $i) {
            $s->sort_order = $i;
            $s->save();
        });

        return redirect()->route('stories.edit', $story)
            ->with('success', 'Step removed.');
    }

    public function moveUp(Story $story, StoryStep $step)
    {
        $prev = $story->steps()
            ->where('sort_order', '<', $step->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($prev) {
            [$step->sort_order, $prev->sort_order] = [$prev->sort_order, $step->sort_order];
            $step->save();
            $prev->save();
        }

        return redirect()->route('stories.edit', $story);
    }

    public function moveDown(Story $story, StoryStep $step)
    {
        $next = $story->steps()
            ->where('sort_order', '>', $step->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            [$step->sort_order, $next->sort_order] = [$next->sort_order, $step->sort_order];
            $step->save();
            $next->save();
        }

        return redirect()->route('stories.edit', $story);
    }
}
