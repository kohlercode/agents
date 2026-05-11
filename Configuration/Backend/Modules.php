<?php

declare(strict_types=1);

use Kohlercode\Agents\Controller\ChatModuleController;
use Kohlercode\Agents\Controller\SettingsModuleController;

return [
    'agents' => [
        'parent' => 'admin',
        'position' => ['after' => 'integrations'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/agents',
        'iconIdentifier' => 'module-agents-group',
        'labels' => 'LLL:EXT:agents/Resources/Private/Language/locallang_mod_agents.xlf',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
    ],
    'agents_chat' => [
        'parent' => 'agents',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/agents/chat',
        'iconIdentifier' => 'module-agents-chat',
        'labels' => 'LLL:EXT:agents/Resources/Private/Language/locallang_mod_chat.xlf',
        'routes' => [
            '_default' => [
                'target' => ChatModuleController::class . '::handleRequest',
            ],
        ],
    ],
    'agents_settings' => [
        'parent' => 'agents',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/agents/settings',
        'iconIdentifier' => 'module-agents-settings',
        'labels' => 'LLL:EXT:agents/Resources/Private/Language/locallang_mod_settings.xlf',
        'routes' => [
            '_default' => [
                'target' => SettingsModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
