<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use Kohlercode\Agents\Service\SettingsService;

#[AsController]
final readonly class SettingsModuleController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private SettingsService $settingsService,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Agents Settings');
        $moduleTemplate->makeDocHeaderModuleMenu();

        $providers = $this->settingsService->listProviders();

        return $moduleTemplate
            ->assignMultiple([
                'ajaxRoutes' => [
                    'getSettings' => 'agents_settings_get',
                    'saveSettings' => 'agents_settings_save',
                    'listProviders' => 'agents_provider_list',
                    'saveProvider' => 'agents_provider_save',
                    'activateProvider' => 'agents_provider_activate',
                ],
                'providers' => $providers,
            ])
            ->renderResponse('Settings/Index');
    }

    public function editProvider(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $postParams = $request->getParsedBody();
        $providerUid = (int)($queryParams['providerUid'] ?? 0);
        $mode = (string)($queryParams['mode'] ?? 'edit');
 
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Agents Provider');
        $moduleTemplate->makeDocHeaderModuleMenu();

        if($mode === 'update'){
            $provider = [
                'uid' => $providerUid,
                'title' => (string)($postParams['title'] ?? 'My Provider'),
                'provider_key' => (string)($postParams['providerKey'] ?? ''),
                'api_key_ref' => (string)($postParams['apiKeyRef'] ?? ''),
                'model_identifier' => (string)($postParams['modelIdentifier'] ?? ''),
                'api_base_url' => (string)($postParams['apiBaseUrl'] ?? ''),
            ];
            $this->settingsService->saveProvider($provider);
        }
        else{
            $provider = $this->settingsService->getProviderByUid($providerUid);
        }

        
        return $moduleTemplate
            ->assignMultiple([
                'provider' => $provider,
                'mode' => $mode,
                'queryParams' => $queryParams,
                'postParams' => $postParams,
            ])
            ->renderResponse('Settings/EditProvider');
    }

    public function addProviderForm(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Agents Provider');
        $moduleTemplate->makeDocHeaderModuleMenu();

        $queryParams = $request->getQueryParams();
        $mode = (string)($queryParams['mode'] ?? 'add');

        if($mode === 'save'){
            $provider = [
                'title' => (string)($postParams['title'] ?? 'My Provider'),
                'provider_key' => (string)($postParams['providerKey'] ?? ''),
                'api_key_ref' => (string)($postParams['apiKeyRef'] ?? ''),
                'model_identifier' => (string)($postParams['modelIdentifier'] ?? ''),
                'api_base_url' => (string)($postParams['apiBaseUrl'] ?? ''),
                'is_active' => (string)($postParams['isActive'] ?? ''),
            ];
            $this->settingsService->addProvider($provider);
        }
        else{
            $provider = [];
        }

        return $moduleTemplate
            ->assignMultiple([
                'ajaxRoutes' => [
                    'saveProvider' => 'agents_provider_save',
                ],
                'mode' => $mode,
            ])
            ->renderResponse('Settings/AddProviderForm');
    }

}
