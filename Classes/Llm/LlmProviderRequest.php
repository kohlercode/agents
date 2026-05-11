<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm;

final readonly class LlmProviderRequest
{
    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     */
    public function __construct(
        public array $messages,
        public array $tools,
        public string $modelIdentifier,
        public float $temperature = 0.2,
    ) {}
}
