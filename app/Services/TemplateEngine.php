<?php

namespace App\Services;

class TemplateEngine
{
    private const PATTERN = '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/';

    public function extractVariables(string $content): array
    {
        preg_match_all(self::PATTERN, $content, $matches);
        return array_values(array_unique($matches[1]));
    }

    public function render(string $content, array $variables, ?array $metadata = null): array
    {
        $missing = [];
        $used = [];

        $rendered = preg_replace_callback(self::PATTERN, function ($matches) use ($variables, $metadata, &$missing, &$used) {
            $name = $matches[1];
            if (array_key_exists($name, $variables)) {
                $used[] = $name;
                return $variables[$name];
            }
            // Check for default in metadata
            if ($metadata && isset($metadata[$name]['default']) && $metadata[$name]['default'] !== null) {
                $used[] = $name;
                return $metadata[$name]['default'];
            }
            $missing[] = $name;
            return $matches[0];
        }, $content);

        return [
            'rendered'          => $rendered,
            'variables_used'    => array_values(array_unique($used)),
            'variables_missing' => array_values(array_unique($missing)),
        ];
    }
}
