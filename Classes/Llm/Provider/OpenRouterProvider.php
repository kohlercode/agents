<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm\Provider;

use Kohlercode\Agents\Llm\LlmHttpClient;
use Kohlercode\Agents\Llm\LlmProviderInterface;
use Kohlercode\Agents\Llm\LlmProviderRequest;
use Kohlercode\Agents\Llm\LlmProviderResponse;
use Kohlercode\Agents\Security\ApiKeyCipher;

final readonly class OpenRouterProvider implements LlmProviderInterface
{
    public function __construct(
        private LlmHttpClient $httpClient,
        private ApiKeyCipher $apiKeyCipher,
    ) {}

    public function supports(string $providerKey): bool
    {
        return $providerKey === 'openrouter';
    }

    public function complete(LlmProviderRequest $request, array $providerConfig): LlmProviderResponse
    {
        $apiKey = $this->resolveApiKey($providerConfig);
        if ($apiKey === '') {
            return new LlmProviderResponse('[OpenRouter API key missing]');
        }

        $baseUrl = trim((string)($providerConfig['api_base_url'] ?? ''));
        if ($baseUrl === '') {
            $baseUrl = 'https://openrouter.ai/api/v1';
        }
        $payload = [
            'model' => $request->modelIdentifier,
            'messages' => $request->messages,
            'tools' => $request->tools,
            'temperature' => $request->temperature,
        ];
        $responseData = $this->httpClient->postJson(rtrim($baseUrl, '/') . '/chat/completions', [
            'Authorization' => 'Bearer ' . $apiKey,
        ], $payload);

        $choice = $responseData['choices'][0] ?? [];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];
        $usage = is_array($responseData['usage'] ?? null) ? $responseData['usage'] : [];

        return new LlmProviderResponse(
            (string)($message['content'] ?? ''),
            (int)($usage['total_tokens'] ?? 0),
            (string)($choice['finish_reason'] ?? ''),
            is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [],
            [
                'provider' => 'openrouter',
                'model' => (string)($responseData['model'] ?? $request->modelIdentifier),
            ]
        );
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
