<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Repository\PageRepository;
use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;

final readonly class GetPageTranslationsTool implements ToolInterface, ToolMetadataInterface
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
        return 'get_page_translations';
    }

    public function getDescription(): string
    {
        return 'Returns all existing translations of a TYPO3 page. Accepts either the default-language page uid or '
            . 'a translated page uid (in which case the canonical default-language uid is resolved automatically).';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'page_uid' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'UID of any page record (default language or a translation).',
                ],
            ],
            'required' => ['page_uid'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $pageUid = (int)($arguments['page_uid'] ?? 0);
        if ($pageUid <= 0) {
            throw new \InvalidArgumentException('Argument "page_uid" must be a positive integer.');
        }

        $page = $this->pageRepository->getPageByUid($pageUid);
        if ($page === null) {
            throw new \RuntimeException(sprintf('Page %d not found.', $pageUid));
        }

        $canonicalUid = (int)($page['sys_language_uid'] ?? 0) === 0
            ? $pageUid
            : (int)($page['l10n_parent'] ?? $pageUid);

        $translations = $this->pageRepository->getTranslationsForPage($canonicalUid);

        return [
            'default_language_page_uid' => $canonicalUid,
            'translation_count' => count($translations),
            'translations' => array_map(
                static fn (array $row): array => [
                    'uid' => (int)$row['uid'],
                    'title' => (string)($row['title'] ?? ''),
                    'sys_language_uid' => (int)($row['sys_language_uid'] ?? 0),
                    'l10n_parent' => (int)($row['l10n_parent'] ?? 0),
                    'hidden' => (int)($row['hidden'] ?? 0),
                ],
                $translations
            ),
        ];
    }
}
