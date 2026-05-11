<?php

declare(strict_types=1);

use Kohlercode\Agents\Backend\Ajax\ChatApiController;
use Kohlercode\Agents\Backend\Ajax\SettingsApiController;

return [
    'agents_chat_list' => [
        'path' => '/agents/chat/list',
        'methods' => ['GET'],
        'target' => ChatApiController::class . '::listChats',
        'inheritAccessFromModule' => 'agents_chat',
    ],
    'agents_chat_create' => [
        'path' => '/agents/chat/create',
        'methods' => ['POST'],
        'target' => ChatApiController::class . '::createChat',
        'inheritAccessFromModule' => 'agents_chat',
    ],
    'agents_chat_messages' => [
        'path' => '/agents/chat/messages',
        'methods' => ['GET'],
        'target' => ChatApiController::class . '::listMessages',
        'inheritAccessFromModule' => 'agents_chat',
    ],
    'agents_chat_send' => [
        'path' => '/agents/chat/send',
        'methods' => ['GET', 'POST'],
        'target' => ChatApiController::class . '::sendMessage',
        'inheritAccessFromModule' => 'agents_chat',
    ],
    'agents_settings_get' => [
        'path' => '/agents/settings/get',
        'methods' => ['GET'],
        'target' => SettingsApiController::class . '::getSettings',
        'inheritAccessFromModule' => 'agents_settings',
    ],
    'agents_settings_save' => [
        'path' => '/agents/settings/save',
        'methods' => ['POST'],
        'target' => SettingsApiController::class . '::saveSettings',
        'inheritAccessFromModule' => 'agents_settings',
    ],
    'agents_provider_list' => [
        'path' => '/agents/provider/list',
        'methods' => ['GET'],
        'target' => SettingsApiController::class . '::listProviders',
        'inheritAccessFromModule' => 'agents_settings',
    ],
    'agents_provider_save' => [
        'path' => '/agents/provider/save',
        'methods' => ['POST'],
        'target' => SettingsApiController::class . '::saveProvider',
        'inheritAccessFromModule' => 'agents_settings',
    ],
    'agents_provider_activate' => [
        'path' => '/agents/provider/activate',
        'methods' => ['POST'],
        'target' => SettingsApiController::class . '::activateProvider',
        'inheritAccessFromModule' => 'agents_settings',
    ],
];
