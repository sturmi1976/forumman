<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Forum Manager - Community Forum & Discussions',
    'description' => 'Modern forum extension for TYPO3. Create discussion boards, threads, replies, and community-driven conversations. Includes pagination, latest activity, user interaction, and scalable architecture.',
    'category' => 'fe',
    'state' => 'stable',

    'author' => 'Andre Lanius',
    'author_email' => 'a-lanius@web.de',
    'author_company' => 'Andre Lanius',

    'version' => '1.6.6',

    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
            'php' => '8.2.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
