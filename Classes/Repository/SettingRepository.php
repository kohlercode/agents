<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

final readonly class SettingRepository extends AbstractRepository
{
    private const TABLE = 'tx_agents_domain_model_setting';

    /**
     * @return array<string, mixed>
     */
    public function getOrCreate(): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if ($row !== false) {
            return $row;
        }

        $timestamp = time();
        $connection = $this->getConnection(self::TABLE);
        $connection->insert(self::TABLE, [
            'pid' => 0,
            'tstamp' => $timestamp,
            'crdate' => $timestamp,
            'cruser_id' => 0,
            'deleted' => 0,
            'hidden' => 0,
            'system_prompt' => '',
            'active_provider_uid' => 0,
            'feature_flags_json' => '{}',
        ]);

        $uid = (int)$connection->lastInsertId(self::TABLE);
        return [
            'uid' => $uid,
            'system_prompt' => '',
            'active_provider_uid' => 0,
            'feature_flags_json' => '{}',
        ];
    }

    public function saveSystemPromptAndActive(string $systemPrompt, int $activeProviderUid): void
    {
        $existing = $this->getOrCreate();
        $uid = (int)$existing['uid'];
        $connection = $this->getConnection(self::TABLE);
        $connection->update(self::TABLE, [
            'tstamp' => time(),
            'system_prompt' => $systemPrompt,
            'active_provider_uid' => $activeProviderUid,
        ], ['uid' => $uid]);
    }
}
