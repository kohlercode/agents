<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Agents',
    'description' => 'TYPO3 backend AI agents modules',
    'category' => 'module',
    'author' => 'Simon Kohler',
    'author_email' => 'simon@kohlercode.com',
    'state' => 'alpha',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
