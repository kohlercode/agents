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

    public function setPinned(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        if ($backendUserId <= 0) {
            return $this->error('Backend user context missing.', 403);
        }

        $payload = $this->readJsonBody($request);
        $chatUid = (int)($payload['chatUid'] ?? 0);
        $pinned = (bool)($payload['pinned'] ?? false);
        if ($chatUid <= 0) {
            return $this->error('Missing required field "chatUid".');
        }

        try {
            $result = $this->chatService->setPinned($chatUid, $backendUserId, $pinned);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 400);
        }

        return $this->success([
            'chatUid' => $chatUid,
            'pinned' => $result['pinned'],
            'sorting' => $result['sorting'],
        ]);
    }

    public function setProvider(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        if ($backendUserId <= 0) {
            return $this->error('Backend user context missing.', 403);
        }

        $payload = $this->readJsonBody($request);
        $chatUid = (int)($payload['chatUid'] ?? 0);
        $providerUid = (int)($payload['providerUid'] ?? 0);
        if ($chatUid <= 0 || $providerUid <= 0) {
            return $this->error('Missing required fields "chatUid" and "providerUid".');
        }

        try {
            $result = $this->chatService->setProvider($chatUid, $backendUserId, $providerUid);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 400);
        }

        return $this->success([
            'chatUid' => $chatUid,
            'providerUid' => $result['providerUid'],
            'modelIdentifier' => $result['modelIdentifier'],
        ]);
    }
}
