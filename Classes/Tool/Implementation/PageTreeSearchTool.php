<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

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
        return 'Searches the page tree below a given root page uid for pages matching a search term.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'site_uid' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Root page uid / mount point page uid used as the search root.',
                ],
                'search_term' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
            ],
            'required' => ['site_uid', 'search_term'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $siteUid = (int)($arguments['site_uid'] ?? 0);
        $searchTerm = trim((string)($arguments['search_term'] ?? ''));

        if ($siteUid <= 0) {
            throw new \InvalidArgumentException('Site uid must be a positive integer.');
        }
        if ($searchTerm === '') {
            throw new \InvalidArgumentException('Search term is required.');
        }

        $pageTree = $this->pageTreeRepository->fetchFilteredTree(
            $searchTerm,
            [$siteUid],
            $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW)
        );

        return [
            'page_tree' => $pageTree,
        ];
    }
}
