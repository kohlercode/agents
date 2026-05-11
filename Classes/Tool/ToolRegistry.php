<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool;

final readonly class ToolRegistry
{
    /**
     * @param iterable<ToolInterface> $tools
     */
    public function __construct(
        private iterable $tools,
    ) {}

    /**
     * @return array<int, ToolInterface>
     */
    public function all(): array
    {
        return array_values(iterator_to_array($this->tools));
    }

    public function getByName(string $name): ?ToolInterface
    {
        foreach ($this->tools as $tool) {
            if ($tool->getName() === $name) {
                return $tool;
            }
        }
        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function asLlmToolDefinitions(): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            $definitions[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getInputSchema(),
                ],
            ];
        }
        return $definitions;
    }
}
