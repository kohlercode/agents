<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm\Provider;

use Kohlercode\Agents\Llm\LlmHttpClient;
use Kohlercode\Agents\Llm\LlmProviderInterface;
use Kohlercode\Agents\Llm\LlmProviderRequest;
use Kohlercode\Agents\Llm\LlmProviderResponse;

final readonly class GoogleProvider implements LlmProviderInterface
{
    public function __construct(
        private LlmHttpClient $httpClient,
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

        $baseUrl = (string)($providerConfig['api_base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta');
        $parts = [];
        foreach ($request->messages as $message) {
            $parts[] = [
                'role' => (string)($message['role'] ?? 'user'),
                'parts' => [['text' => (string)($message['content'] ?? '')]],
            ];
        }
        $responseData = $this->httpClient->postJson(
            rtrim($baseUrl, '/') . '/models/' . rawurlencode($request->modelIdentifier) . ':generateContent?key=' . rawurlencode($apiKey),
            [],
            ['contents' => $parts]
        );

        $candidate = $responseData['candidates'][0] ?? [];
        $candidateContent = is_array($candidate['content'] ?? null) ? $candidate['content'] : [];
        $candidateParts = $candidateContent['parts'] ?? [];
        $firstPart = is_array($candidateParts[0] ?? null) ? $candidateParts[0] : [];

        return new LlmProviderResponse(
            (string)($firstPart['text'] ?? ''),
            0,
            (string)($candidate['finishReason'] ?? ''),
            [],
            [
                'provider' => 'google',
                'model' => $request->modelIdentifier,
            ]
        );
    }

    private function resolveApiKey(array $providerConfig): string
    {
        $envName = trim((string)($providerConfig['api_key_ref'] ?? ''));
        if ($envName === '') {
            return '';
        }
        return (string)getenv($envName);
    }
}
