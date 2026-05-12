<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_setting',
        'label' => 'uid',
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
        'searchFields' => 'system_prompt',
        'iconfile' => 'EXT:agents/Resources/Public/Icons/module-agents-settings.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, system_prompt, active_provider_uid, pinned_chats_limit, feature_flags_json'],
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
        'system_prompt' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_setting.system_prompt',
            'config' => [
                'type' => 'text',
                'rows' => 6,
            ],
        ],
        'active_provider_uid' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_setting.active_provider_uid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_agents_domain_model_provider',
                'default' => 0,
            ],
        ],
        'feature_flags_json' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_setting.feature_flags_json',
            'config' => [
                'type' => 'text',
                'rows' => 4,
            ],
        ],
        'pinned_chats_limit' => [
            'exclude' => true,
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_setting.pinned_chats_limit',
            'config' => [
                'type' => 'number',
                'default' => 20,
                'range' => [
                    'lower' => 1,
                    'upper' => 999,
                ],
            ],
        ],
    ],
];
