<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm\Provider;

use Kohlercode\Agents\Llm\LlmHttpClient;
use Kohlercode\Agents\Llm\LlmProviderInterface;
use Kohlercode\Agents\Llm\LlmProviderRequest;
use Kohlercode\Agents\Llm\LlmProviderResponse;
use Kohlercode\Agents\Security\ApiKeyCipher;

final readonly class GoogleProvider implements LlmProviderInterface
{
    public function __construct(
        private LlmHttpClient $httpClient,
        private ApiKeyCipher $apiKeyCipher,
    ) {}

    public function supports(string $providerKey): bool
    {
        return $providerKey === 'google';
    }

    public function complete(LlmProviderRequest $request, array $providerConfig): LlmProviderResponse
    {
        $apiKey = $this->resolveApiKey($providerConfig);
        if ($apiKey === '') {
            return new LlmProviderResponse('[Google API key missing]');
        }

        $baseUrl = trim((string)($providerConfig['api_base_url'] ?? ''));
        if ($baseUrl === '') {
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
        }

        $systemInstruction = $this->buildSystemInstruction($request->messages);
        $contents = $this->buildContents($request->messages);

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $request->temperature,
            ],
        ];
        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        $geminiTools = $this->mapOpenAiToolsToGemini($request->tools);
        if ($geminiTools !== []) {
            $payload['tools'] = $geminiTools;
            $payload['toolConfig'] = [
                'functionCallingConfig' => [
                    'mode' => 'AUTO',
                ],
            ];
        }

        $responseData = $this->httpClient->postJson(
            rtrim($baseUrl, '/') . '/models/' . rawurlencode($request->modelIdentifier) . ':generateContent?key=' . rawurlencode($apiKey),
            [],
            $payload
        );

        if (isset($responseData['error']) && is_array($responseData['error'])) {
            $message = (string)($responseData['error']['message'] ?? 'Google API error');
            return new LlmProviderResponse(
                $message,
                0,
                'error',
                [],
                [
                    'provider' => 'google',
                    'model' => $request->modelIdentifier,
                ]
            );
        }

        $usage = is_array($responseData['usageMetadata'] ?? null) ? $responseData['usageMetadata'] : [];
        $tokenUsage = (int)($usage['totalTokenCount'] ?? 0);

        $candidate = is_array($responseData['candidates'][0] ?? null) ? $responseData['candidates'][0] : [];
        $candidateContent = is_array($candidate['content'] ?? null) ? $candidate['content'] : [];
        $candidateParts = $candidateContent['parts'] ?? [];
        if (!is_array($candidateParts)) {
            $candidateParts = [];
        }

        $text = '';
        $toolCalls = [];
        foreach ($candidateParts as $part) {
            if (!is_array($part)) {
                continue;
            }
            if (isset($part['text'])) {
                $text .= (string)$part['text'];
            }
            $functionCall = $part['functionCall'] ?? null;
            if (is_array($functionCall)) {
                $parsed = $this->mapGeminiFunctionCallToOpenAiToolCall($functionCall);
                if ($parsed !== null) {
                    $toolCalls[] = $parsed;
                }
            }
        }

        return new LlmProviderResponse(
            $text,
            $tokenUsage,
            (string)($candidate['finishReason'] ?? ''),
            $toolCalls,
            [
                'provider' => 'google',
                'model' => $request->modelIdentifier,
            ]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return list<array{role: string, parts: list<array{text: string}>}>|array{}
     */
    private function buildContents(array $messages): array
    {
        $contents = [];
        foreach ($messages as $message) {
            $role = (string)($message['role'] ?? 'user');
            if ($role === 'system') {
                continue;
            }
            $geminiRole = $role === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => (string)($message['content'] ?? '')]],
            ];
        }
        return $contents;
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array{parts: list<array{text: string}>}|null
     */
    private function buildSystemInstruction(array $messages): ?array
    {
        $texts = [];
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $t = trim((string)($message['content'] ?? ''));
                if ($t !== '') {
                    $texts[] = $t;
                }
            }
        }
        if ($texts === []) {
            return null;
        }
        return [
            'parts' => [['text' => implode("\n\n", $texts)]],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $openAiTools
     * @return list<array{functionDeclarations: list<array<string, mixed>>}>
     */
    private function mapOpenAiToolsToGemini(array $openAiTools): array
    {
        $declarations = [];
        foreach ($openAiTools as $tool) {
            if (($tool['type'] ?? '') !== 'function') {
                continue;
            }
            $fn = is_array($tool['function'] ?? null) ? $tool['function'] : [];
            $name = trim((string)($fn['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $declaration = [
                'name' => $name,
                'description' => (string)($fn['description'] ?? ''),
            ];
            $params = $fn['parameters'] ?? null;
            if (is_array($params) && $params !== []) {
                $normalized = $this->normalizeParameterSchemaForGemini($params);
                if ($normalized !== []) {
                    $declaration['parameters'] = $normalized;
                }
            }
            $declarations[] = $declaration;
        }
        if ($declarations === []) {
            return [];
        }
        return [['functionDeclarations' => $declarations]];
    }

    /**
     * Gemini generateContent expects OpenAPI-style parameter schemas: the Schema.type enum uses
     * uppercase names (OBJECT, STRING, …). An OBJECT with an empty properties map is rejected.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function normalizeParameterSchemaForGemini(array $schema): array
    {
        $schema = $this->uppercaseGeminiSchemaTypes($schema);
        $this->ensureObjectSchemasHaveProperties($schema);
        return $schema;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function uppercaseGeminiSchemaTypes(array $node): array
    {
        static $typeMap = [
            'object' => 'OBJECT',
            'string' => 'STRING',
            'integer' => 'INTEGER',
            'number' => 'NUMBER',
            'boolean' => 'BOOLEAN',
            'array' => 'ARRAY',
            'null' => 'NULL',
        ];
        foreach ($node as $key => $value) {
            if ($key === 'type' && is_string($value)) {
                $lower = strtolower($value);
                if (isset($typeMap[$lower])) {
                    $node[$key] = $typeMap[$lower];
                }
                continue;
            }
            if (is_array($value)) {
                $node[$key] = array_is_list($value)
                    ? array_map(
                        fn (mixed $item): mixed => is_array($item) ? $this->uppercaseGeminiSchemaTypes($item) : $item,
                        $value
                    )
                    : $this->uppercaseGeminiSchemaTypes($value);
            }
        }
        return $node;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function ensureObjectSchemasHaveProperties(array &$node): void
    {
        $type = $node['type'] ?? '';
        if ($type === 'OBJECT') {
            $props = $node['properties'] ?? null;
            if (!is_array($props) || $props === []) {
                $node['properties'] = [
                    'unusedOptionalHint' => [
                        'type' => 'STRING',
                        'description' => 'Optional. Leave empty or omit; placeholder so the schema is valid for the API.',
                    ],
                ];
                $required = $node['required'] ?? null;
                if (is_array($required)) {
                    $node['required'] = array_values(
                        array_filter($required, static fn (mixed $r): bool => is_string($r) && $r !== 'unusedOptionalHint')
                    );
                    if ($node['required'] === []) {
                        unset($node['required']);
                    }
                }
            }
        }
        foreach ($node as $key => &$value) {
            if ($key === 'properties' && is_array($value) && !array_is_list($value)) {
                foreach ($value as &$propSchema) {
                    if (is_array($propSchema)) {
                        $this->ensureObjectSchemasHaveProperties($propSchema);
                    }
                }
                unset($propSchema);
            } elseif ($key === 'items' && is_array($value)) {
                $this->ensureObjectSchemasHaveProperties($value);
            } elseif ($key === 'anyOf' && is_array($value)) {
                foreach ($value as &$sub) {
                    if (is_array($sub)) {
                        $this->ensureObjectSchemasHaveProperties($sub);
                    }
                }
                unset($sub);
            }
        }
        unset($value);
    }

    /**
     * @param array<string, mixed> $functionCall
     * @return array{id: string, type: string, function: array{name: string, arguments: string}}|null
     */
    private function mapGeminiFunctionCallToOpenAiToolCall(array $functionCall): ?array
    {
        $name = trim((string)($functionCall['name'] ?? ''));
        if ($name === '') {
            return null;
        }
        $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];
        if (!is_array($args)) {
            $args = [];
        }
        return [
            'id' => 'call_' . bin2hex(random_bytes(8)),
            'type' => 'function',
            'function' => [
                'name' => $name,
                'arguments' => json_encode($args, JSON_THROW_ON_ERROR),
            ],
        ];
    }

    private function resolveApiKey(array $providerConfig): string
    {
        $storedValue = trim((string)($providerConfig['api_key_ref'] ?? ''));
        if ($storedValue === '') {
            return '';
        }

        $decrypted = $this->apiKeyCipher->decrypt($storedValue);
        if ($decrypted !== '') {
            return $decrypted;
        }

        return '';
    }
}
