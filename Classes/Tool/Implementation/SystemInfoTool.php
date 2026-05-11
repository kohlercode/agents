<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;

final readonly class SystemInfoTool implements ToolInterface
{
    public function __construct(
        private SiteFinder $siteFinder,
    ) {}

    public function getName(): string
    {
        return 'system_info';
    }

    public function getDescription(): string
    {
        return 'Returns TYPO3 system and site information.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $sites = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            $languages = [];
            foreach ($site->getLanguages() as $language) {
                $languages[] = $language->getTitle();
            }
            $sites[] = [
                'identifier' => $site->getIdentifier(),
                'base' => (string)$site->getBase(),
                'languages' => $languages,
            ];
        }

        return [
            'backendUserId' => $backendUserId,
            'typo3Version' => (new Typo3Version())->getVersion(),
            'siteCount' => count($sites),
            'sites' => $sites,
        ];
    }
}
