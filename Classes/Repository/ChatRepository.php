<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use Doctrine\DBAL\ParameterType;

final readonly class ChatRepository extends AbstractRepository
{
    private const TABLE = 'tx_agents_domain_model_chat';

    /**
     * Pinned chats first (highest sorting first, ties broken by tstamp),
     * then the rest of the chats sorted by tstamp DESC.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByBackendUser(int $backendUserId, int $limit = 100): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'created_by_be_user',
                    $queryBuilder->createNamedParameter($backendUserId, ParameterType::INTEGER)
                )
            )
            ->orderBy('pinned', 'DESC')
            ->addOrderBy('sorting', 'DESC')
            ->addOrderBy('tstamp', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }

    public function countPinnedByBackendUser(int $backendUserId): int
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'created_by_be_user',
                    $queryBuilder->createNamedParameter($backendUserId, ParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    'pinned',
                    $queryBuilder->createNamedParameter(1, ParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    public function getMaxPinnedSortingByBackendUser(int $backendUserId): int
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $value = $queryBuilder
            ->selectLiteral('COALESCE(MAX(sorting), 0) AS max_sorting')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'created_by_be_user',
                    $queryBuilder->createNamedParameter($backendUserId, ParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    'pinned',
                    $queryBuilder->createNamedParameter(1, ParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$value;
    }

    public function setPinned(int $chatUid, int $backendUserId, bool $pinned, int $sorting = 0): bool
    {
        $connection = $this->getConnection(self::TABLE);
        $affected = $connection->update(
            self::TABLE,
            [
                'pinned' => $pinned ? 1 : 0,
                'sorting' => $pinned ? $sorting : 0,
                'tstamp' => time(),
            ],
            [
                'uid' => $chatUid,
                'created_by_be_user' => $backendUserId,
            ]
        );

        return $affected > 0;
    }

    public function setProvider(int $chatUid, int $backendUserId, int $providerUid, string $modelIdentifier): bool
    {
        $connection = $this->getConnection(self::TABLE);
        $affected = $connection->update(
            self::TABLE,
            [
                'provider_uid' => $providerUid,
                'model_identifier' => mb_substr($modelIdentifier, 0, 255),
                'tstamp' => time(),
            ],
            [
                'uid' => $chatUid,
                'created_by_be_user' => $backendUserId,
            ]
        );

        return $affected > 0;
    }

    public function create(string $title, int $providerUid, string $modelIdentifier, int $backendUserId): int
    {
        $timestamp = time();
        $connection = $this->getConnection(self::TABLE);
        $connection->insert(self::TABLE, [
            'pid' => 0,
            'tstamp' => $timestamp,
            'crdate' => $timestamp,
            'cruser_id' => $backendUserId,
            'deleted' => 0,
            'hidden' => 0,
            'title' => mb_substr(trim($title), 0, 255),
            'provider_uid' => $providerUid,
            'model_identifier' => mb_substr($modelIdentifier, 0, 255),
            'created_by_be_user' => $backendUserId,
        ]);

        return (int)$connection->lastInsertId(self::TABLE);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByUid(int $chatUid): ?array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($chatUid, ParameterType::INTEGER))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? $row : null;
    }
}
