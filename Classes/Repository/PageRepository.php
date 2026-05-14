<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class PageRepository extends AbstractRepository
{
    private const TABLE = 'pages';
    private const TABLE_CONTENT = 'tt_content';

    public function getPageByUid(int $uid, bool $includeDeleted = false): ?array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER))
            );

        if (!$includeDeleted) {
            $deleteField = $this->deleteField(self::TABLE);
            if ($deleteField !== null) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq($deleteField, $queryBuilder->createNamedParameter(0, ParameterType::INTEGER))
                );
            }
        }

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        return $row !== false ? $row : null;
    }

    /**
     * Returns all (non-deleted) translations of the given default-language page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTranslationsForPage(int $uid): array
    {
        $languageField = $this->languageField(self::TABLE);
        $transOrigPointerField = $this->transOrigPointerField(self::TABLE);

        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $queryBuilder
            ->select('uid', 'pid', 'title', $languageField, $transOrigPointerField, 'hidden')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    $transOrigPointerField,
                    $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)
                )
            )
            ->orderBy($languageField, 'ASC');

        $this->applyDeletedRestriction($queryBuilder, self::TABLE);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findTranslationByLanguage(int $uid, int $targetLanguageUid): ?array
    {
        $languageField = $this->languageField(self::TABLE);
        $transOrigPointerField = $this->transOrigPointerField(self::TABLE);

        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $queryBuilder
            ->select('uid', 'pid', 'title', $languageField, $transOrigPointerField, 'hidden')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    $transOrigPointerField,
                    $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter($targetLanguageUid, ParameterType::INTEGER)
                )
            )
            ->setMaxResults(1);

        $this->applyDeletedRestriction($queryBuilder, self::TABLE);

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        return $row !== false ? $row : null;
    }

    /**
     * Returns tt_content rows on the given page filtered by language (default: 0 = default language).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getContentElementsOnPage(int $pageUid, int $sysLanguageUid = 0): array
    {
        $languageField = $this->languageField(self::TABLE_CONTENT);

        $queryBuilder = $this->createQueryBuilder(self::TABLE_CONTENT);
        $queryBuilder
            ->select('uid', 'pid', 'header', 'CType', 'colPos', $languageField, 'hidden', 'sorting')
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter($sysLanguageUid, ParameterType::INTEGER)
                )
            )
            ->orderBy('colPos', 'ASC')
            ->addOrderBy('sorting', 'ASC');

        $this->applyDeletedRestriction($queryBuilder, self::TABLE_CONTENT);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Localizes a page record via DataHandler.
     *
     * @return array{newUid: int, errors: array<int, string>}
     */
    public function localizePage(int $uid, int $targetLanguageUid): array
    {
        return $this->localizeRecord(self::TABLE, $uid, $targetLanguageUid);
    }

    /**
     * Localizes a tt_content record via DataHandler.
     *
     * @return array{newUid: int, errors: array<int, string>}
     */
    public function localizeContentElement(int $uid, int $targetLanguageUid): array
    {
        return $this->localizeRecord(self::TABLE_CONTENT, $uid, $targetLanguageUid);
    }

    /**
     * @return array{newUid: int, errors: array<int, string>}
     */
    private function localizeRecord(string $table, int $uid, int $targetLanguageUid): array
    {
        // DataHandler is stateful; always use a fresh instance per cmdmap.
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $cmd = [
            $table => [
                $uid => [
                    'localize' => $targetLanguageUid,
                ],
            ],
        ];
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $newUid = (int)($dataHandler->copyMappingArray[$table][$uid] ?? 0);

        return [
            'newUid' => $newUid,
            'errors' => array_values(array_map('strval', $dataHandler->errorLog)),
        ];
    }

    private function applyDeletedRestriction(QueryBuilder $queryBuilder, string $table): void
    {
        $deleteField = $this->deleteField($table);
        if ($deleteField === null) {
            return;
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $deleteField,
                $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
            )
        );
    }
}
