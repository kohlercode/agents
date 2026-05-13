<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;

final class PageTreeSearchTool implements ToolInterface, ToolMetadataInterface
{
    public function __construct(
        private PageTreeRepository $pageTreeRepository,
    ) {}

    public function getSourceExtensionKey(): string
    {
        return 'agents';
    }

    public function getName(): string
    {
        return 'page_tree_search';
    }

    public function getDescription(): string
    {
        return 'Search the page tree of a given site (by uid) for a specific page (by search term) and returns the structure of the page and its parent pages.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'site_uid' => ['type' => 'integer', 'minimum' => 1],
                'search_term' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
            ],
            'required' => ['site_uid', 'search_term'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {

        $siteUid = $arguments['site_uid'] ?? null;
        $searchTerm = $arguments['search_term'] ?? null;
        if($siteUid === null || $siteUid === ''){
            throw new \InvalidArgumentException('Site uid is required.');
        }
        if($searchTerm === null || $searchTerm === ''){
            throw new \InvalidArgumentException('Search term is required.');
        }
        $pageTree = $this->pageTreeRepository->findInPageTree($searchTerm, [$siteUid], '');
        return [
            'page_tree' => $pageTree,
        ];

    }
}
