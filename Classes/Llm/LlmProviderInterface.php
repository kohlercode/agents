<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm;

interface LlmProviderInterface
{
    public function supports(string $providerKey): bool;

    /**
     * @param array<string, mixed> $providerConfig
     */
    public function complete(LlmProviderRequest $request, array $providerConfig): LlmProviderResponse;
}
