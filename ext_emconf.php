<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Forum Manager',
    'description' => 'A modern TYPO3 forum extension for private messaging, threads, and community discussions.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'Andre Lanius',
    'author_email' => 'a-lanius@web.de',
    'author_company' => 'Andre Lanius',
    'version' => '1.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
