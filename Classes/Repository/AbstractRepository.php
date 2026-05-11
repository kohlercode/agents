<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

abstract readonly class AbstractRepository
{
    public function __construct(
        protected ConnectionPool $connectionPool,
    ) {}

    protected function getConnection(string $table): Connection
    {
        return $this->connectionPool->getConnectionForTable($table);
    }

    protected function createQueryBuilder(string $table): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($table);
    }
}
