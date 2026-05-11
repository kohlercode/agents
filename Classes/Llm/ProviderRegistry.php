<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Llm;

final readonly class ProviderRegistry
{
    /**
     * @param iterable<LlmProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
    ) {}

    public function resolve(string $providerKey): LlmProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($providerKey)) {
                return $provider;
            }
        }

        throw new \RuntimeException(sprintf('No provider implementation found for "%s".', $providerKey));
    }
}
