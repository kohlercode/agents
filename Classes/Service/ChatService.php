<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Service;

use Kohlercode\Agents\Llm\JsonSchemaForLlm;
use Kohlercode\Agents\Llm\LlmProviderRequest;
use Kohlercode\Agents\Llm\ProviderRegistry;
use Kohlercode\Agents\Repository\ChatRepository;
use Kohlercode\Agents\Repository\MessageRepository;
use Kohlercode\Agents\Repository\ProviderRepository;
use Kohlercode\Agents\Repository\SettingRepository;
use Kohlercode\Agents\Tool\ToolExecutionGateway;
use Kohlercode\Agents\Tool\ToolRegistry;

final readonly class ChatService
{
    public function __construct(
        private ChatRepository $chatRepository,
        private MessageRepository $messageRepository,
        private ProviderRepository $providerRepository,
        private SettingRepository $settingRepository,
        private ProviderRegistry $providerRegistry,
        private ToolRegistry $toolRegistry,
        private ToolExecutionGateway $toolExecutionGateway,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listChats(int $backendUserId): array
    {
        return $this->chatRepository->listByBackendUser($backendUserId);
    }

    public function createChat(string $title, int $backendUserId): int
    {
        $settings = $this->settingRepository->getOrCreate();
        $providerUid = (int)($settings['active_provider_uid'] ?? 0);
        $provider = $providerUid > 0 ? $this->providerRepository->getByUid($providerUid) : $this->providerRepository->findActive();
        $providerUid = (int)($provider['uid'] ?? 0);
        $modelIdentifier = (string)($provider['model_identifier'] ?? '');

        return $this->chatRepository->create($title, $providerUid, $modelIdentifier, $backendUserId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMessages(int $chatUid, int $backendUserId): array
    {
        $chat = $this->chatRepository->getByUid($chatUid);
        if ($chat === null || (int)($chat['created_by_be_user'] ?? 0) !== $backendUserId) {
            throw new \RuntimeException('Chat not found or access denied.');
        }
        return $this->messageRepository->listByChat($chatUid);
    }

    /**
     * @return array{pinned: bool, sorting: int}
     */
    public function setPinned(int $chatUid, int $backendUserId, bool $pinned): array
    {
        $chat = $this->chatRepository->getByUid($chatUid);
        if ($chat === null || (int)($chat['created_by_be_user'] ?? 0) !== $backendUserId) {
            throw new \RuntimeException('Chat not found or access denied.');
        }

        $sorting = 0;
        if ($pinned) {
            $currentlyPinned = (int)($chat['pinned'] ?? 0) === 1;
            if (!$currentlyPinned) {
                $limit = $this->resolvePinnedChatsLimit();
                $currentCount = $this->chatRepository->countPinnedByBackendUser($backendUserId);
                if ($currentCount >= $limit) {
                    throw new \RuntimeException(sprintf(
                        'You have reached the maximum of %d pinned chats. Unpin one before pinning another.',
                        $limit
                    ));
                }
            }
            $sorting = $this->chatRepository->getMaxPinnedSortingByBackendUser($backendUserId) + 1;
        }

        $this->chatRepository->setPinned($chatUid, $backendUserId, $pinned, $sorting);

        return ['pinned' => $pinned, 'sorting' => $sorting];
    }

    private function resolvePinnedChatsLimit(): int
    {
        $settings = $this->settingRepository->getOrCreate();
        $limit = (int)($settings['pinned_chats_limit'] ?? SettingRepository::DEFAULT_PINNED_CHATS_LIMIT);
        return $limit > 0 ? $limit : SettingRepository::DEFAULT_PINNED_CHATS_LIMIT;
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(int $chatUid, string $message, int $backendUserId): array
    {
        $chat = $this->chatRepository->getByUid($chatUid);
        if ($chat === null) {
            throw new \RuntimeException('Chat not found.');
        }
        if ((int)($chat['created_by_be_user'] ?? 0) !== $backendUserId) {
            throw new \RuntimeException('Access denied for this chat.');
        }

        $this->messageRepository->addMessage($chatUid, 'user', $message, $backendUserId);

        $provider = $this->resolveProviderForChat($chat);
        if ($provider === null) {
            $assistantText = 'No active provider configured.';
            $this->messageRepository->addMessage($chatUid, 'assistant', $assistantText, $backendUserId);
            return ['assistantMessage' => $assistantText, 'toolResults' => []];
        }

        $llmMessages = $this->buildLlmMessages($chatUid);
        $systemPrompt = (string)($this->settingRepository->getOrCreate()['system_prompt'] ?? '');
        if ($systemPrompt !== '') {
            array_unshift($llmMessages, [
                'role' => 'system',
                'content' => $systemPrompt,
            ]);
        }

        $providerClient = $this->providerRegistry->resolve((string)$provider['provider_key']);
        $toolDefinitions = JsonSchemaForLlm::relaxToolParameterSchemas($this->toolRegistry->asLlmToolDefinitions());
        $response = $providerClient->complete(
            new LlmProviderRequest(
                $llmMessages,
                $toolDefinitions,
                (string)($chat['model_identifier'] ?: $provider['model_identifier']),
            ),
            $provider
        );

        $toolResults = $this->executeToolCalls($response->toolCalls, $backendUserId);
        $assistantText = $response->content;
        if (trim($assistantText) === '' && $response->finishReason === 'MALFORMED_FUNCTION_CALL') {
            $assistantText = 'The model attempted to call a tool but returned an invalid tool payload (MALFORMED_FUNCTION_CALL). '
                . 'This often happens with strict JSON Schema or large arguments; try again with a shorter request or a different model.';
        }
        if ($toolResults !== []) {
            $assistantText .= "\n\n" . $this->buildToolSummaryText($toolResults);
        }

        $this->messageRepository->addMessage(
            $chatUid,
            'assistant',
            $assistantText,
            $backendUserId,
            $response->tokenUsage,
            $response->finishReason,
            $response->meta,
            $response->toolCalls
        );

        return [
            'assistantMessage' => $assistantText,
            'toolResults' => $toolResults,
        ];
    }

    /**
     * @param array<string, mixed> $chat
     * @return array<string, mixed>|null
     */
    private function resolveProviderForChat(array $chat): ?array
    {
        $chatProviderUid = (int)($chat['provider_uid'] ?? 0);
        if ($chatProviderUid > 0) {
            $chatProvider = $this->providerRepository->getByUid($chatProviderUid);
            if ($chatProvider !== null) {
                return $chatProvider;
            }
        }

        $settings = $this->settingRepository->getOrCreate();
        $activeProviderUid = (int)($settings['active_provider_uid'] ?? 0);
        if ($activeProviderUid > 0) {
            return $this->providerRepository->getByUid($activeProviderUid);
        }

        return $this->providerRepository->findActive();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildLlmMessages(int $chatUid): array
    {
        $messages = [];
        foreach ($this->messageRepository->listByChat($chatUid) as $message) {
            $messages[] = [
                'role' => (string)$message['role'],
                'content' => (string)$message['content'],
            ];
        }
        return $messages;
    }

    /**
     * @param array<int, array<string, mixed>> $toolCalls
     * @return array<int, array<string, mixed>>
     */
    private function executeToolCalls(array $toolCalls, int $backendUserId): array
    {
        $results = [];
        foreach ($toolCalls as $toolCall) {
            $function = is_array($toolCall['function'] ?? null) ? $toolCall['function'] : [];
            $toolName = (string)($function['name'] ?? '');
            if ($toolName === '') {
                continue;
            }

            $argumentsRaw = (string)($function['arguments'] ?? '{}');
            $arguments = json_decode($argumentsRaw, true);
            if (!is_array($arguments)) {
                $arguments = [];
            }

            try {
                $results[] = [
                    'tool' => $toolName,
                    'result' => $this->toolExecutionGateway->execute($toolName, $arguments, $backendUserId),
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'tool' => $toolName,
                    'error' => $exception->getMessage(),
                ];
            }
        }
        return $results;
    }

    /**
     * @param array<int, array<string, mixed>> $toolResults
     */
    private function buildToolSummaryText(array $toolResults): string
    {
        $lines = [];
        foreach ($toolResults as $toolResult) {
            $toolName = (string)($toolResult['tool'] ?? '');
            if ($toolName === '') {
                continue;
            }

            if (array_key_exists('error', $toolResult)) {
                $lines[] = sprintf(
                    'Tool "%s" failed: %s',
                    $toolName,
                    (string)$toolResult['error']
                );
                continue;
            }

            $result = $toolResult['result'] ?? null;
            if ($toolName === 'create_page' && is_array($result)) {
                $createdPageUid = (int)($result['createdPageUid'] ?? 0);
                $dryRun = (bool)($result['dryRun'] ?? false);
                $title = trim((string)($result['title'] ?? ''));
                $parentPid = (int)($result['parentPid'] ?? 0);

                if ($dryRun) {
                    $lines[] = sprintf(
                        'Planned to create a page "%s" below page %d. No changes were made (dry run).',
                        $title !== '' ? $title : '[no title]',
                        $parentPid
                    );
                } elseif ($createdPageUid > 0) {
                    $lines[] = sprintf(
                        'Created page %d%s.',
                        $createdPageUid,
                        $title !== '' ? sprintf(' with title "%s"', $title) : ''
                    );
                } else {
                    $lines[] = 'Page creation tool reported an unexpected result.';
                }
                continue;
            }

            if ($toolName === 'system_info' && is_array($result)) {
                $typo3Version = (string)($result['typo3Version'] ?? '');
                $siteCount = (int)($result['siteCount'] ?? 0);
                $lines[] = sprintf(
                    'TYPO3 %s with %d site%s configured.',
                    $typo3Version !== '' ? $typo3Version : '[unknown version]',
                    $siteCount,
                    $siteCount === 1 ? '' : 's'
                );
                continue;
            }

            $lines[] = sprintf(
                'Tool "%s" completed. Result: %s',
                $toolName,
                (string)json_encode($result, JSON_UNESCAPED_UNICODE)
            );
        }

        return $lines === [] ? 'Tools executed, but no usable results were returned.' : implode("\n", $lines);
    }
}
