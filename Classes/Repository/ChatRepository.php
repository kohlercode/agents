<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use Doctrine\DBAL\ParameterType;

final readonly class ChatRepository extends AbstractRepository
{
    private const TABLE = 'tx_agents_domain_model_chat';

    /**
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
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
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
