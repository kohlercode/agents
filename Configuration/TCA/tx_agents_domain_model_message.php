<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message',
        'label' => 'role',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'searchFields' => 'role,content',
        'iconfile' => 'EXT:agents/Resources/Public/Icons/module-agents-chat.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, chat_uid, role, content, token_usage, finish_reason, tool_calls_json, response_meta_json'],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'chat_uid' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.chat_uid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_agents_domain_model_chat',
                'required' => true,
                'default' => 0,
            ],
        ],
        'role' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.role',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['System', 'system'],
                    ['User', 'user'],
                    ['Assistant', 'assistant'],
                    ['Tool', 'tool'],
                ],
                'required' => true,
                'default' => 'user',
            ],
        ],
        'content' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.content',
            'config' => [
                'type' => 'text',
                'rows' => 6,
            ],
        ],
        'token_usage' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.token_usage',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
        'finish_reason' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.finish_reason',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 64,
                'default' => '',
            ],
        ],
        'tool_calls_json' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.tool_calls_json',
            'config' => [
                'type' => 'text',
                'rows' => 4,
            ],
        ],
        'response_meta_json' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_message.response_meta_json',
            'config' => [
                'type' => 'text',
                'rows' => 4,
            ],
        ],
    ],
];
