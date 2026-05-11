<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider',
        'label' => 'title',
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
        'searchFields' => 'title,provider_key,model_identifier',
        'iconfile' => 'EXT:agents/Resources/Public/Icons/module-agents-settings.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, title, provider_key, api_base_url, api_key_ref, model_identifier, configuration_json, is_active'],
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
        'title' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'size' => 40,
                'max' => 255,
            ],
        ],
        'provider_key' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.provider_key',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Google', 'google'],
                    ['DeepSeek', 'deepseek'],
                    ['OpenRouter', 'openrouter'],
                ],
                'required' => true,
                'default' => 'openrouter',
            ],
        ],
        'api_base_url' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.api_base_url',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'default' => '',
            ],
        ],
        'api_key_ref' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.api_key_ref',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'default' => '',
            ],
        ],
        'model_identifier' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.model_identifier',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'default' => '',
            ],
        ],
        'configuration_json' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.configuration_json',
            'config' => [
                'type' => 'text',
                'rows' => 5,
            ],
        ],
        'is_active' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_provider.is_active',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ],
];
