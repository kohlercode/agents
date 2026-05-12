<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Service;

use Kohlercode\Agents\Repository\ProviderRepository;
use Kohlercode\Agents\Repository\SettingRepository;
use Kohlercode\Agents\Security\ApiKeyCipher;

final readonly class SettingsService
{
    private const ALLOWED_PROVIDER_KEYS = ['google', 'deepseek', 'openrouter'];

    public function __construct(
        private SettingRepository $settingRepository,
        private ProviderRepository $providerRepository,
        private ApiKeyCipher $apiKeyCipher,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settingRepository->getOrCreate();
    }

    public function saveSettings(
        string $systemPrompt,
        int $activeProviderUid,
        string $backendModulePosition,
        int $pinnedChatsLimit
    ): void {
        if ($activeProviderUid > 0) {
            $this->providerRepository->setActive($activeProviderUid);
        }
        $this->settingRepository->saveAllSettings(
            $systemPrompt,
            $activeProviderUid,
            $backendModulePosition,
            $this->clampPinnedChatsLimit($pinnedChatsLimit)
        );
    }

    private function clampPinnedChatsLimit(int $value): int
    {
        if ($value < 1) {
            return 1;
        }
        if ($value > 999) {
            return 999;
        }
        return $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProviders(): array
    {
        $providers = $this->providerRepository->listAll();
        foreach ($providers as &$provider) {
            $provider['has_api_key'] = trim((string)($provider['api_key_ref'] ?? '')) !== '';
            unset($provider['api_key_ref']);
        }
        return $providers;
    }

    /**
     * @param array<string, mixed> $providerData
     */
    public function saveProvider(array $providerData): int
    {
        $providerKey = (string)($providerData['provider_key'] ?? '');
        if (!in_array($providerKey, self::ALLOWED_PROVIDER_KEYS, true)) {
            throw new \InvalidArgumentException('Unsupported provider key.');
        }

        $incomingApiKey = trim((string)($providerData['api_key'] ?? ''));
        if ($incomingApiKey !== '') {
            $providerData['api_key_ref'] = $this->apiKeyCipher->encrypt($incomingApiKey);
        } elseif ((int)($providerData['uid'] ?? 0) > 0) {
            $existing = $this->providerRepository->getByUid((int)$providerData['uid']);
            $providerData['api_key_ref'] = (string)($existing['api_key_ref'] ?? '');
        }
        unset($providerData['api_key']);

        return $this->providerRepository->save($providerData);
    }

    public function activateProvider(int $providerUid): void
    {
        $this->providerRepository->setActive($providerUid);
        $settings = $this->settingRepository->getOrCreate();
        $this->settingRepository->saveAllSettings(
            (string)($settings['system_prompt'] ?? ''),
            $providerUid,
            (string)($settings['backend_module_position'] ?? ''),
            (int)($settings['pinned_chats_limit'] ?? SettingRepository::DEFAULT_PINNED_CHATS_LIMIT)
        );
    }
}
