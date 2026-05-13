<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool;

/**
 * Allows execution of any tool registered in the DI container (tag agents.tool).
 * Third-party extensions register tools by implementing ToolInterface and tagging their service.
 */
final readonly class ToolPolicy
{
    public function __construct(
        private ToolRegistry $toolRegistry,
    ) {}

    public function isAllowed(string $toolName): bool
    {
        return $this->toolRegistry->getByName($toolName) !== null;
    }
}
