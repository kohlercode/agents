<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Repository\PageRepository;
use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

final readonly class TranslatePageTool implements ToolInterface, ToolMetadataInterface
{
    public function __construct(
        private PageRepository $pageRepository,
        private SiteFinder $siteFinder,
    ) {}

    public function getSourceExtensionKey(): string
    {
        return 'agents';
    }

    public function getName(): string
    {
        return 'translate_page';
    }

    public function getDescription(): string
    {
        return 'Translates an existing TYPO3 page (and optionally its content elements) into a target language. '
            . 'Performs preflight checks: page exists, site language is configured and enabled, no existing translation, '
            . 'and lists content elements that would be translated. Use dry_run to validate without writing.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'page_uid' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'UID of the default-language page to translate.',
                ],
                'target_language_uid' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'sys_language_uid of the target language. Must be configured and enabled on the page\'s site.',
                ],
                'cascade_content_elements' => [
                    'type' => 'boolean',
                    'description' => 'When true (default), also localizes every default-language tt_content element on the page.',
                ],
                'dry_run' => [
                    'type' => 'boolean',
                    'description' => 'When true, only runs validation and returns a preflight report without writing.',
                ],
            ],
            'required' => ['page_uid', 'target_language_uid'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $pageUid = (int)($arguments['page_uid'] ?? 0);
        $targetLanguageUid = (int)($arguments['target_language_uid'] ?? 0);
        $cascade = (bool)($arguments['cascade_content_elements'] ?? true);
        $dryRun = (bool)($arguments['dry_run'] ?? false);

        if ($pageUid <= 0) {
            throw new \InvalidArgumentException('Argument "page_uid" must be a positive integer.');
        }
        if ($targetLanguageUid <= 0) {
            throw new \InvalidArgumentException(
                'Argument "target_language_uid" must be a positive integer. Translating to the default language (0) is not supported.'
            );
        }

        $page = $this->pageRepository->getPageByUid($pageUid);
        if ($page === null) {
            return [
                'ok' => false,
                'reason' => 'page_not_found',
                'message' => sprintf('Page with uid %d not found or deleted.', $pageUid),
            ];
        }

        if ((int)($page['sys_language_uid'] ?? 0) !== 0) {
            return [
                'ok' => false,
                'reason' => 'page_is_translation',
                'message' => 'The given page is itself a translation. Translate its default-language original instead.',
                'default_language_page_uid' => (int)($page['l10n_parent'] ?? 0),
            ];
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException) {
            return [
                'ok' => false,
                'reason' => 'site_not_found',
                'message' => sprintf('No site configuration found for page %d.', $pageUid),
            ];
        }

        $availableLanguages = [];
        $targetLanguage = null;
        foreach ($site->getLanguages() as $lang) {
            $availableLanguages[] = $this->describeLanguage($lang);
            if ($lang->getLanguageId() === $targetLanguageUid) {
                $targetLanguage = $lang;
            }
        }

        if ($targetLanguage === null) {
            return [
                'ok' => false,
                'reason' => 'language_not_configured',
                'message' => sprintf(
                    'Language uid %d is not configured for site "%s".',
                    $targetLanguageUid,
                    $site->getIdentifier()
                ),
                'site' => $site->getIdentifier(),
                'available_languages' => $availableLanguages,
            ];
        }

        if (!$targetLanguage->isEnabled()) {
            return [
                'ok' => false,
                'reason' => 'language_disabled',
                'message' => sprintf(
                    'Language uid %d ("%s") is configured but disabled for site "%s".',
                    $targetLanguageUid,
                    $targetLanguage->getTitle(),
                    $site->getIdentifier()
                ),
                'site' => $site->getIdentifier(),
                'available_languages' => $availableLanguages,
            ];
        }

        $existing = $this->pageRepository->findTranslationByLanguage($pageUid, $targetLanguageUid);
        if ($existing !== null) {
            return [
                'ok' => false,
                'reason' => 'translation_exists',
                'message' => sprintf(
                    'Page %d already has a translation in language %d (uid %d).',
                    $pageUid,
                    $targetLanguageUid,
                    (int)$existing['uid']
                ),
                'existing_translation_uid' => (int)$existing['uid'],
                'existing_translation' => [
                    'uid' => (int)$existing['uid'],
                    'title' => (string)($existing['title'] ?? ''),
                    'sys_language_uid' => (int)($existing['sys_language_uid'] ?? 0),
                    'hidden' => (int)($existing['hidden'] ?? 0),
                ],
            ];
        }

        $contentElements = $this->pageRepository->getContentElementsOnPage($pageUid, 0);

        $preflight = [
            'page' => [
                'uid' => $pageUid,
                'title' => (string)($page['title'] ?? ''),
            ],
            'site' => [
                'identifier' => $site->getIdentifier(),
                'rootPageId' => (int)$site->getRootPageId(),
            ],
            'target_language' => $this->describeLanguage($targetLanguage),
            'available_languages' => $availableLanguages,
            'cascade_content_elements' => $cascade,
            'content_elements_in_default_language' => array_map(
                static fn (array $ce): array => [
                    'uid' => (int)$ce['uid'],
                    'header' => (string)($ce['header'] ?? ''),
                    'CType' => (string)($ce['CType'] ?? ''),
                    'colPos' => (int)($ce['colPos'] ?? 0),
                    'hidden' => (int)($ce['hidden'] ?? 0),
                ],
                $contentElements
            ),
        ];

        if ($dryRun) {
            return [
                'ok' => true,
                'dry_run' => true,
                'message' => 'Preflight passed. No changes were made.',
                'preflight' => $preflight,
            ];
        }

        $pageResult = $this->pageRepository->localizePage($pageUid, $targetLanguageUid);
        if ($pageResult['newUid'] <= 0) {
            return [
                'ok' => false,
                'reason' => 'localize_failed',
                'message' => 'DataHandler did not produce a translated page record. See errors for details.',
                'errors' => $pageResult['errors'],
                'preflight' => $preflight,
            ];
        }

        $contentElementResults = [];
        if ($cascade) {
            foreach ($contentElements as $ce) {
                $ceUid = (int)$ce['uid'];
                $ceResult = $this->pageRepository->localizeContentElement($ceUid, $targetLanguageUid);
                $contentElementResults[] = [
                    'originalUid' => $ceUid,
                    'translatedUid' => $ceResult['newUid'],
                    'errors' => $ceResult['errors'],
                ];
            }
        }

        return [
            'ok' => true,
            'dry_run' => false,
            'message' => sprintf(
                'Page %d translated to language %d as new page uid %d.',
                $pageUid,
                $targetLanguageUid,
                $pageResult['newUid']
            ),
            'translated_page_uid' => $pageResult['newUid'],
            'translated_content_elements' => $contentElementResults,
            'preflight' => $preflight,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeLanguage(SiteLanguage $language): array
    {
        return [
            'languageId' => $language->getLanguageId(),
            'title' => $language->getTitle(),
            'locale' => (string)$language->getLocale(),
            'enabled' => $language->isEnabled(),
        ];
    }
}
