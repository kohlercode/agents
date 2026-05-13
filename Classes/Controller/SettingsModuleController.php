<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Controller;

use Kohlercode\Agents\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;

#[AsController]
final readonly class SettingsModuleController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private SettingsService $settingsService,
        private PageTreeRepository $pageTreeRepository,
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
                'toolsListLabelsJson' => $this->encodeToolsListLabelsForDataAttribute($request),
            ])
            ->renderResponse('Settings/Index');
    }

    private function encodeToolsListLabelsForDataAttribute(ServerRequestInterface $request): string
    {
        $labels = [
            'empty' => LocalizationUtility::translate(
                'LLL:EXT:agents/Resources/Private/Language/locallang.xlf:settings.availableTools.empty',
                null,
                null,
                null,
                $request
            ) ?? '',
            'parameters' => LocalizationUtility::translate(
                'LLL:EXT:agents/Resources/Private/Language/locallang.xlf:settings.availableTools.parameters',
                null,
                null,
                null,
                $request
            ) ?? '',
            'sourceExtension' => LocalizationUtility::translate(
                'LLL:EXT:agents/Resources/Private/Language/locallang.xlf:settings.availableTools.sourceExtension',
                null,
                null,
                null,
                $request
            ) ?? '',
        ];

        return json_encode($labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
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
                'api_key' => (string)($postParams['apiKeyRef'] ?? ''),
                'model_identifier' => (string)($postParams['modelIdentifier'] ?? ''),
                'api_base_url' => (string)($postParams['apiBaseUrl'] ?? ''),
                'is_active' => (int)($postParams['isActive'] ?? 0),
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
        $postParams = $request->getParsedBody();
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
