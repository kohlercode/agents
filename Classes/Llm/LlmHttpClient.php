<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm;

use TYPO3\CMS\Core\Http\RequestFactory;

final readonly class LlmHttpClient
{
    public function __construct(
        private RequestFactory $requestFactory,
    ) {}

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function postJson(string $url, array $headers, array $payload): array
    {
        $attempt = 0;
        $maxAttempts = 3;
        do {
            try {
                $response = $this->requestFactory->request($url, 'POST', [
                    'headers' => array_merge(
                        ['Content-Type' => 'application/json'],
                        $headers
                    ),
                    'body' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'timeout' => 25,
                    'connect_timeout' => 8,
                ]);
                break;
            } catch (\Throwable $exception) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw $exception;
                }
                usleep($attempt * 200000);
            }
        } while (true);

        $body = (string)$response->getBody();
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
