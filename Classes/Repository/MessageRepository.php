<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Repository;

use Doctrine\DBAL\ParameterType;

final readonly class MessageRepository extends AbstractRepository
{
    private const TABLE = 'tx_agents_domain_model_message';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByChat(int $chatUid): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE);

        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('chat_uid', $queryBuilder->createNamedParameter($chatUid, ParameterType::INTEGER))
            )
            ->orderBy('crdate', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map([$this, 'normalizeMessageRow'], $rows);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function addMessage(
        int $chatUid,
        string $role,
        string $content,
        int $backendUserId,
        int $tokenUsage = 0,
        string $finishReason = '',
        array $meta = [],
        array $toolCalls = [],
        array $artifacts = [],
    ): int {
        $timestamp = time();
        $connection = $this->getConnection(self::TABLE);
        $connection->insert(self::TABLE, [
            'pid' => 0,
            'tstamp' => $timestamp,
            'crdate' => $timestamp,
            'cruser_id' => $backendUserId,
            'deleted' => 0,
            'hidden' => 0,
            'chat_uid' => $chatUid,
            'role' => mb_substr(trim($role), 0, 32),
            'content' => $content,
            'token_usage' => $tokenUsage,
            'finish_reason' => mb_substr($finishReason, 0, 64),
            'tool_calls_json' => $toolCalls !== [] ? json_encode($toolCalls, JSON_THROW_ON_ERROR) : null,
            'response_meta_json' => $meta !== [] ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            'message_artifacts_json' => $artifacts !== [] ? json_encode($artifacts, JSON_THROW_ON_ERROR) : null,
        ]);

        return (int)$connection->lastInsertId(self::TABLE);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeMessageRow(array $row): array
    {
        $artifacts = [];
        $rawArtifacts = (string)($row['message_artifacts_json'] ?? '');
        if ($rawArtifacts !== '') {
            $decoded = json_decode($rawArtifacts, true);
            if (is_array($decoded)) {
                $artifacts = $decoded;
            }
        }

        $row['artifacts'] = $artifacts;
        unset($row['message_artifacts_json']);

        return $row;
    }
}
