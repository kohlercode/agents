<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Backend\Ajax;

use Kohlercode\Agents\Service\ChatService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;

#[AsController]
final class ChatApiController extends AbstractApiController
{
    public function __construct(
        private ChatService $chatService,
    ) {}

    public function listChats(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        if ($backendUserId <= 0) {
            return $this->error('Backend user context missing.', 403);
        }

        return $this->success([
            'chats' => $this->chatService->listChats($backendUserId),
        ]);
    }

    public function createChat(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        if ($backendUserId <= 0) {
            return $this->error('Backend user context missing.', 403);
        }

        $payload = $this->readJsonBody($request);
        $title = trim((string)($payload['title'] ?? ''));
        $title = $title !== '' ? $title : 'New chat';

        $chatUid = $this->chatService->createChat($title, $backendUserId);
        return $this->success([
            'chatUid' => $chatUid,
        ]);
    }

    public function listMessages(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        if ($backendUserId <= 0) {
            return $this->error('Backend user context missing.', 403);
        }
        $chatUid = (int)($request->getQueryParams()['chatUid'] ?? 0);
        if ($chatUid <= 0) {
            return $this->error('Missing required query parameter "chatUid".');
        }

        try {
            $messages = $this->chatService->listMessages($chatUid, $backendUserId);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 403);
        }

        return $this->success(['messages' => $messages]);
    }

    public function sendMessage(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        if ($backendUserId <= 0) {
            return $this->error('Backend user context missing.', 403);
        }

        $payload = $this->readJsonBody($request);
        $chatUid = (int)($payload['chatUid'] ?? 0);
        $message = trim((string)($payload['message'] ?? ''));
        if ($chatUid <= 0 || $message === '') {
            return $this->error('Missing required fields "chatUid" and "message".');
        }

        try {
            $result = $this->chatService->sendMessage($chatUid, $message, $backendUserId);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 500);
        }

        return $this->success($result);
    }
}
