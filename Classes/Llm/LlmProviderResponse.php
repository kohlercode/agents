<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm;

final readonly class LlmProviderResponse
{
    /**
     * @param array<int, array<string, mixed>> $toolCalls
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $content,
        public int $tokenUsage = 0,
        public string $finishReason = '',
        public array $toolCalls = [],
        public array $meta = [],
    ) {}
}
