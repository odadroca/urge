<?php

namespace App\Services;

use App\Models\Prompt;
use App\Models\PromptEnvironment;

class TemplateEngine
{
    private const PATTERN = '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/';
    private const INCLUDE_PATTERN = '/\{\{>([a-zA-Z0-9_-]+)\}\}/';

    /**
     * Extract variable placeholders from content (does not follow includes).
     */
    public function extractVariables(string $content): array
    {
        preg_match_all(self::PATTERN, $content, $matches);
        return array_values(array_unique($matches[1]));
    }

    /**
     * Extract include references ({{>slug}}) from content.
     */
    public function extractIncludes(string $content): array
    {
        preg_match_all(self::INCLUDE_PATTERN, $content, $matches);
        return array_values(array_unique($matches[1]));
    }

    /**
     * Resolve all {{>slug}} includes recursively, then render variables.
     *
     * @param  string      $content    The template content
     * @param  array       $variables  Key-value pairs for substitution
     * @param  array|null  $metadata   Variable metadata (with defaults)
     * @param  string|null $environment  Environment name to propagate to included prompts
     * @return array{rendered: string, variables_used: string[], variables_missing: string[], includes_resolved: string[]}
     */
    public function render(string $content, array $variables, ?array $metadata = null, ?string $environment = null): array
    {
        $includesResolved = [];
        $resolvedContent = $this->resolveIncludes($content, $environment, [], $includesResolved);

        // Now render variables on the fully resolved content
        // Merge metadata from included prompts
        $mergedMetadata = $metadata ?? [];
        foreach ($includesResolved as $slug) {
            $included = Prompt::where('slug', $slug)->with('activeVersion')->first();
            if ($included?->activeVersion?->variable_metadata) {
                // Parent metadata takes precedence
                $mergedMetadata = array_merge($included->activeVersion->variable_metadata, $mergedMetadata);
            }
        }
        if (empty($mergedMetadata)) {
            $mergedMetadata = null;
        }

        $missing = [];
        $used = [];

        $rendered = preg_replace_callback(self::PATTERN, function ($matches) use ($variables, $mergedMetadata, &$missing, &$used) {
            $name = $matches[1];
            if (array_key_exists($name, $variables)) {
                $used[] = $name;
                return $variables[$name];
            }
            if ($mergedMetadata && isset($mergedMetadata[$name]['default']) && $mergedMetadata[$name]['default'] !== null) {
                $used[] = $name;
                return $mergedMetadata[$name]['default'];
            }
            $missing[] = $name;
            return $matches[0];
        }, $resolvedContent);

        return [
            'rendered'           => $rendered,
            'variables_used'     => array_values(array_unique($used)),
            'variables_missing'  => array_values(array_unique($missing)),
            'includes_resolved'  => array_values(array_unique($includesResolved)),
        ];
    }

    /**
     * Recursively resolve {{>slug}} includes in content.
     *
     * @param  string      $content    The template content
     * @param  string|null $environment  Environment to use for version resolution
     * @param  array       $chain      Slugs already in the inclusion chain (circular reference detection)
     * @param  array       &$resolved  Accumulator for all resolved include slugs
     * @return string The content with all includes expanded
     *
     * @throws \RuntimeException on circular reference or max depth exceeded
     */
    private function resolveIncludes(string $content, ?string $environment, array $chain, array &$resolved): string
    {
        $maxDepth = config('urge.max_include_depth', 10);

        return preg_replace_callback(self::INCLUDE_PATTERN, function ($matches) use ($environment, $chain, &$resolved, $maxDepth) {
            $slug = $matches[1];

            if (in_array($slug, $chain, true)) {
                $path = implode(' → ', [...$chain, $slug]);
                throw new \RuntimeException("Circular include detected: {$path}");
            }

            if (count($chain) >= $maxDepth) {
                throw new \RuntimeException("Max include depth ({$maxDepth}) exceeded.");
            }

            $prompt = Prompt::where('slug', $slug)->with('activeVersion')->first();
            if (!$prompt || !$prompt->activeVersion) {
                // Leave the include tag as-is if the prompt doesn't exist or has no active version
                return $matches[0];
            }

            // Resolve version: environment first, then active
            $version = $prompt->activeVersion;
            if ($environment) {
                $env = PromptEnvironment::where('prompt_id', $prompt->id)
                    ->where('name', $environment)
                    ->first();
                if ($env) {
                    $envVersion = $prompt->versions()->where('id', $env->prompt_version_id)->first();
                    if ($envVersion) {
                        $version = $envVersion;
                    }
                }
            }

            $resolved[] = $slug;

            // Recursively resolve nested includes
            return $this->resolveIncludes($version->content, $environment, [...$chain, $slug], $resolved);
        }, $content);
    }
}
