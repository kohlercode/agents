<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Backend\Ajax;

use Kohlercode\Agents\Repository\SettingRepository;
use Kohlercode\Agents\Service\SettingsService;
use Kohlercode\Agents\Tool\ToolRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;

#[AsController]
final class SettingsApiController extends AbstractApiController
{
    public function __construct(
        private SettingsService $settingsService,
        private ToolRegistry $toolRegistry,
    ) {}

    public function getSettings(ServerRequestInterface $request): ResponseInterface
    {
        $tools = [];
        foreach ($this->toolRegistry->all() as $tool) {
            $tools[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getInputSchema(),
            ];
        }

        return $this->success([
            'settings' => $this->settingsService->getSettings(),
            'tools' => $tools,
        ]);
    }

    public function saveSettings(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $this->readJsonBody($request);
        $systemPrompt = (string)($payload['systemPrompt'] ?? '');
        $activeProviderUid = (int)($payload['activeProviderUid'] ?? 0);
        $backendModulePosition = (string)($payload['backendModulePosition'] ?? '');
        $pinnedChatsLimit = (int)($payload['pinnedChatsLimit'] ?? SettingRepository::DEFAULT_PINNED_CHATS_LIMIT);
        $this->settingsService->saveSettings(
            $systemPrompt,
            $activeProviderUid,
            $backendModulePosition,
            $pinnedChatsLimit
        );

        return $this->success();
    }

    public function listProviders(ServerRequestInterface $request): ResponseInterface
    {
        return $this->success([
            'providers' => $this->settingsService->listProviders(),
        ]);
    }

    public function saveProvider(ServerRequestInterface $request): ResponseInterface
    {
        $backendUserId = $this->resolveBackendUserId();
        $payload = $this->readJsonBody($request);
        $providerData = [
            'uid' => (int)($payload['uid'] ?? 0),
            'cruser_id' => $backendUserId,
            'title' => (string)($payload['title'] ?? ''),
            'provider_key' => (string)($payload['providerKey'] ?? ''),
            'api_base_url' => (string)($payload['apiBaseUrl'] ?? ''),
            'api_key' => (string)($payload['apiKey'] ?? ''),
            'model_identifier' => (string)($payload['modelIdentifier'] ?? ''),
            'configuration_json' => (string)($payload['configurationJson'] ?? '{}'),
            'is_active' => (int)($payload['isActive'] ?? 0),
        ];
        if ($providerData['title'] === '' || $providerData['provider_key'] === '') {
            return $this->error('Missing required fields "title" and "providerKey".');
        }

        try {
            $providerUid = $this->settingsService->saveProvider($providerData);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage());
        }
        return $this->success([
            'providerUid' => $providerUid,
        ]);
    }

    public function activateProvider(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $this->readJsonBody($request);
        $providerUid = (int)($payload['providerUid'] ?? 0);
        if ($providerUid <= 0) {
            return $this->error('Missing required field "providerUid".');
        }

        $this->settingsService->activateProvider($providerUid);
        return $this->success();
    }
}
