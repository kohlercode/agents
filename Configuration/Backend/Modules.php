<?php

declare(strict_types=1);

use Kohlercode\Agents\Controller\ChatModuleController;
use Kohlercode\Agents\Controller\SettingsModuleController;

return [
    'integrations_agents_chat' => [
        'parent' => 'integrations',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/integrations/agents/chat',
        'iconIdentifier' => 'module-agents-chat',
        'labels' => 'LLL:EXT:agents/Resources/Private/Language/locallang_mod_chat.xlf',
        'routes' => [
            '_default' => [
                'target' => ChatModuleController::class . '::handleRequest',
            ],
        ],
    ],
    'integrations_agents_settings' => [
        'parent' => 'integrations',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/integrations/agents/settings',
        'iconIdentifier' => 'module-agents-settings',
        'labels' => 'LLL:EXT:agents/Resources/Private/Language/locallang_mod_settings.xlf',
        'routes' => [
            '_default' => [
                'target' => SettingsModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
