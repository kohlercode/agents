<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Service;

use Kohlercode\Agents\Repository\ProviderRepository;
use Kohlercode\Agents\Repository\SettingRepository;

final readonly class SettingsService
{
    private const ALLOWED_PROVIDER_KEYS = ['google', 'deepseek', 'openrouter'];

    public function __construct(
        private SettingRepository $settingRepository,
        private ProviderRepository $providerRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settingRepository->getOrCreate();
    }

    public function saveSettings(string $systemPrompt, int $activeProviderUid): void
    {
        if ($activeProviderUid > 0) {
            $this->providerRepository->setActive($activeProviderUid);
        }
        $this->settingRepository->saveSystemPromptAndActive($systemPrompt, $activeProviderUid);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProviders(): array
    {
        return $this->providerRepository->listAll();
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
        return $this->providerRepository->save($providerData);
    }

    public function activateProvider(int $providerUid): void
    {
        $this->providerRepository->setActive($providerUid);
        $settings = $this->settingRepository->getOrCreate();
        $this->settingRepository->saveSystemPromptAndActive((string)($settings['system_prompt'] ?? ''), $providerUid);
    }
}
