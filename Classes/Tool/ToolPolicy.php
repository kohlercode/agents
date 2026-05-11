<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool;

final class ToolPolicy
{
    /**
     * @var array<string, bool>
     */
    private array $allowlist = [
        'system_info' => true,
        'create_page' => true,
    ];

    public function isAllowed(string $toolName): bool
    {
        return $this->allowlist[$toolName] ?? false;
    }
}
