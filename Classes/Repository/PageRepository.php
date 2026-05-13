<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use Doctrine\DBAL\ParameterType;

final readonly class PageRepository extends AbstractRepository{

    private const TABLE = 'pages';

    public function getPageByUid(int $uid, bool $includeDeleted = false): ?array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative();
        return $row !== false ? $row : null;
    }
}