<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool;

final readonly class ToolExecutionGateway
{
    public function __construct(
        private ToolRegistry $toolRegistry,
        private ToolPolicy $toolPolicy,
    ) {}

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function execute(string $toolName, array $arguments, int $backendUserId): array
    {
        if (!$this->toolPolicy->isAllowed($toolName)) {
            throw new \RuntimeException(sprintf('Tool "%s" is not allowed by policy.', $toolName));
        }

        $tool = $this->toolRegistry->getByName($toolName);
        if ($tool === null) {
            throw new \RuntimeException(sprintf('Tool "%s" is not registered.', $toolName));
        }

        return $tool->execute($arguments, $backendUserId);
    }
}
