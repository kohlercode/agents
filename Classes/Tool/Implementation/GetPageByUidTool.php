<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;
use Kohlercode\Agents\Repository\PageRepository;

final class GetPageByUidTool implements ToolInterface, ToolMetadataInterface
{
    public function __construct(
        private PageRepository $pageRepository,
    ) {}

    public function getSourceExtensionKey(): string
    {
        return 'agents';
    }

    public function getName(): string
    {
        return 'get_page_by_uid';
    }

    public function getDescription(): string
    {
        return 'Returns a single TYPO3 page by its uid.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'uid' => ['type' => 'integer', 'minimum' => 1],
                'include_deleted' => ['type' => 'boolean'],
            ],
            'required' => ['uid'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $uid = (int)($arguments['uid'] ?? 0);
        $includeDeleted = (bool)($arguments['include_deleted'] ?? false);
        $page = $this->pageRepository->getPageByUid($uid, $includeDeleted);
        if ($page === null) {
            throw new \RuntimeException('Page not found.');
        }
        return [
            'page' => $page,
        ];
    }
}
