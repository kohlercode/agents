<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm;

/**
 * Gemini (direct or via some gateways) often fails tool calls with finishReason
 * MALFORMED_FUNCTION_CALL when JSON Schema sets additionalProperties to false.
 * Relaxing schemas slightly improves compatibility without changing TYPO3 tool handlers.
 */
final class JsonSchemaForLlm
{
    /**
     * @param array<int, array<string, mixed>> $openAiStyleTools
     * @return array<int, array<string, mixed>>
     */
    public static function relaxToolParameterSchemas(array $openAiStyleTools): array
    {
        $out = [];
        foreach ($openAiStyleTools as $tool) {
            if (($tool['type'] ?? '') !== 'function') {
                $out[] = $tool;
                continue;
            }
            $fn = is_array($tool['function'] ?? null) ? $tool['function'] : [];
            if (isset($fn['parameters']) && is_array($fn['parameters'])) {
                /** @var array<string, mixed> $params */
                $params = $fn['parameters'];
                $fn['parameters'] = self::stripAdditionalPropertiesRecursive($params);
            }
            $tool['function'] = $fn;
            $out[] = $tool;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<int|string, mixed>
     */
    private static function stripAdditionalPropertiesRecursive(array $node): array
    {
        unset($node['additionalProperties']);
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = array_is_list($value)
                    ? array_map(
                        static fn (mixed $item): mixed => is_array($item)
                            ? self::stripAdditionalPropertiesRecursive($item)
                            : $item,
                        $value
                    )
                    : self::stripAdditionalPropertiesRecursive($value);
            }
        }
        return $node;
    }
}
