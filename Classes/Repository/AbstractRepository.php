<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;

abstract readonly class AbstractRepository
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected DataHandler $dataHandler,
    ) {}

    protected function getConnection(string $table): Connection
    {
        return $this->connectionPool->getConnectionForTable($table);
    }

    protected function createQueryBuilder(string $table): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($table);
    }

    /**
     * Resolves the column that holds the language uid for a given table from TCA.
     * Falls back to the conventional 'sys_language_uid' if no TCA entry exists.
     */
    protected function languageField(string $table): string
    {
        $field = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        return is_string($field) && $field !== '' ? $field : 'sys_language_uid';
    }

    /**
     * Resolves the column that points to the default-language record for a given table from TCA.
     * NOTE: 'pages' uses 'l10n_parent' but 'tt_content' uses 'l18n_parent' — read it from TCA.
     */
    protected function transOrigPointerField(string $table): string
    {
        $field = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        return is_string($field) && $field !== '' ? $field : 'l10n_parent';
    }

    /**
     * Resolves the soft-delete column for a given table from TCA, or null when the table has none.
     */
    protected function deleteField(string $table): ?string
    {
        $field = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        return is_string($field) && $field !== '' ? $field : null;
    }
}
