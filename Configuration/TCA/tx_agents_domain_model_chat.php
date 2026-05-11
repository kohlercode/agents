<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_chat',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,model_identifier',
        'iconfile' => 'EXT:agents/Resources/Public/Icons/module-agents-chat.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, title, provider_uid, model_identifier, created_by_be_user'],
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
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_chat.title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'size' => 40,
                'max' => 255,
            ],
        ],
        'provider_uid' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_chat.provider_uid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_agents_domain_model_provider',
                'default' => 0,
            ],
        ],
        'model_identifier' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_chat.model_identifier',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'default' => '',
            ],
        ],
        'created_by_be_user' => [
            'label' => 'LLL:EXT:agents/Resources/Private/Language/locallang_db.xlf:tx_agents_domain_model_chat.created_by_be_user',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
    ],
];
