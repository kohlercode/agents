<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Service;

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
        $response = $providerClient->complete(
            new LlmProviderRequest(
                $llmMessages,
                $this->toolRegistry->asLlmToolDefinitions(),
                (string)($chat['model_identifier'] ?: $provider['model_identifier']),
            ),
            $provider
        );

        $toolResults = $this->executeToolCalls($response->toolCalls, $backendUserId);
        $assistantText = $response->content;
        if ($toolResults !== []) {
            $assistantText .= "\n\nTool results:\n" . (string)json_encode($toolResults, JSON_PRETTY_PRINT);
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
}
