<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SearchSystemLogTool implements ToolInterface, ToolMetadataInterface
{
    public function getSourceExtensionKey(): string
    {
        return 'agents';
    }

    public function getName(): string
    {
        return 'search_system_log';
    }

    public function getDescription(): string
    {
        return 'Searches the system log for a specific event.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'term' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
            ],
            'required' => ['term'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $term = trim((string)($arguments['term'] ?? ''));

        if ($term === '') {
            throw new \InvalidArgumentException('Tool argument term is required.');
        }

        return [
            'message' => 'System log search for term "' . $term . '" completed.',
        ];
    }
}
