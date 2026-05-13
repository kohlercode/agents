<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CreatePageTool implements ToolInterface
{
    public function getName(): string
    {
        return 'create_page';
    }

    public function getDescription(): string
    {
        return 'Creates a basic TYPO3 page below a parent page id.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parentPid' => ['type' => 'integer', 'minimum' => 1],
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                'seo_title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                'description' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                'og_title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                'og_description' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
                'doktype' => ['type' => 'integer', 'minimum' => 1],
            ],
            'required' => ['parentPid', 'title'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $parentPid = (int)($arguments['parentPid'] ?? 0);
        $title = trim((string)($arguments['title'] ?? ''));
        $seo_title = trim((string)($arguments['seo_title'] ?? ''));
        $description = trim((string)($arguments['description'] ?? ''));
        $og_title = trim((string)($arguments['og_title'] ?? ''));
        $og_description = trim((string)($arguments['og_description'] ?? ''));
        $doktype = (int)($arguments['doktype'] ?? 1);

        if ($parentPid <= 0 || $title === '') {
            throw new \InvalidArgumentException('Tool arguments parentPid and title are required.');
        }

        $newId = 'NEW' . md5((string)microtime(true));
        $data = [
            'pages' => [
                $newId => [
                    'pid' => $parentPid,
                    'title' => mb_substr($title, 0, 255),
                    'seo_title' => mb_substr($seo_title, 0, 255),
                    'description' => mb_substr($description, 0, 255),
                    'og_title' => mb_substr($og_title, 0, 255),
                    'og_description' => mb_substr($og_description, 0, 255),
                    'doktype' => $doktype,
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        $createdPageUid = (int)($dataHandler->substNEWwithIDs[$newId] ?? 0);

        if ($createdPageUid <= 0) {
            throw new \RuntimeException('Page creation failed.');
        }

        return [
            'createdPageUid' => $createdPageUid,
            'createdByBackendUser' => $backendUserId,
        ];
    }
}
