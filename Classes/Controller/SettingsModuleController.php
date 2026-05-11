<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

#[AsController]
final readonly class SettingsModuleController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Agents Settings');
        $moduleTemplate->makeDocHeaderModuleMenu();

        return $moduleTemplate
            ->assignMultiple([
                'ajaxRoutes' => [
                    'getSettings' => 'agents_settings_get',
                    'saveSettings' => 'agents_settings_save',
                    'listProviders' => 'agents_provider_list',
                    'saveProvider' => 'agents_provider_save',
                    'activateProvider' => 'agents_provider_activate',
                ],
            ])
            ->renderResponse('Settings/Index');
    }
}
