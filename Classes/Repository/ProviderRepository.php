<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use Doctrine\DBAL\ParameterType;

final readonly class ProviderRepository extends AbstractRepository
{
    private const TABLE = 'tx_agents_domain_model_provider';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);

        return $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->orderBy('title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActive(): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);

        return $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('is_active', $queryBuilder->createNamedParameter(1, ParameterType::INTEGER))
            )
            ->orderBy('title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActive(): ?array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('is_active', $queryBuilder->createNamedParameter(1, ParameterType::INTEGER))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByUid(int $uid): ?array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $providerData
     */
    public function save(array $providerData): int
    {
        $timestamp = time();
        $record = [
            'pid' => 0,
            'tstamp' => $timestamp,
            'crdate' => $timestamp,
            'cruser_id' => (int)($providerData['cruser_id'] ?? 0),
            'deleted' => 0,
            'hidden' => 0,
            'title' => mb_substr((string)($providerData['title'] ?? ''), 0, 255),
            'provider_key' => mb_substr((string)($providerData['provider_key'] ?? ''), 0, 32),
            'api_base_url' => mb_substr((string)($providerData['api_base_url'] ?? ''), 0, 255),
            'api_key_ref' => mb_substr((string)($providerData['api_key_ref'] ?? ''), 0, 255),
            'model_identifier' => mb_substr((string)($providerData['model_identifier'] ?? ''), 0, 255),
            'configuration_json' => (string)($providerData['configuration_json'] ?? ''),
            'is_active' => (int)($providerData['is_active'] ?? 0),
        ];
        $connection = $this->getConnection(self::TABLE);
        $uid = (int)($providerData['uid'] ?? 0);
        if ($uid > 0) {
            unset($record['crdate']);
            $connection->update(self::TABLE, $record, ['uid' => $uid]);
            return $uid;
        }

        $connection->insert(self::TABLE, $record);
        return (int)$connection->lastInsertId(self::TABLE);
    }

    public function setActive(int $uid): void
    {
        $connection = $this->getConnection(self::TABLE);
        $connection->update(self::TABLE, ['is_active' => 1], ['uid' => $uid]);
    }

}
