<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LlmResponse;
use App\Models\PromptRun;
use Illuminate\Http\Request;

class LlmResponseController extends Controller
{
    public function rate(Request $request, PromptRun $run, LlmResponse $response)
    {
        if ($response->prompt_run_id !== $run->id) {
            abort(404);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $response->update([
            'rating'   => $validated['rating'],
            'rated_by' => auth()->id(),
            'rated_at' => now(),
        ]);

        return response()->noContent();
    }

    public function export(PromptRun $run, LlmResponse $response)
    {
        if ($response->prompt_run_id !== $run->id) {
            abort(404);
        }

        $run->load(['prompt', 'version', 'creator']);
        $response->load(['provider', 'rater']);

        $variables = $run->variables_used ?? [];
        $varLines = collect($variables)->map(fn ($v, $k) => "- **{$k}:** {$v}")->implode("\n");
        $ratingLine = $response->rating
            ? "\n\n---\n\n*Rating: {$response->rating}/5" . ($response->rater ? " — rated by {$response->rater->name}" : '') . '*'
            : '';

        $md = "# {$run->prompt->name} — {$response->model_used}\n\n";
        $md .= "**Run:** {$run->created_at->format('Y-m-d H:i:s')}\n";
        $md .= "**Version:** v{$run->version->version_number}\n";
        if ($varLines) {
            $md .= "\n**Variables:**\n{$varLines}\n";
        }
        $md .= "\n---\n\n## Prompt\n\n{$run->rendered_content}\n\n";
        $md .= "---\n\n## Response\n\n{$response->response_text}";
        $md .= $ratingLine . "\n";

        $filename = \Illuminate\Support\Str::slug("{$run->prompt->name}-{$response->model_used}-run{$run->id}") . '.md';

        return response($md, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
