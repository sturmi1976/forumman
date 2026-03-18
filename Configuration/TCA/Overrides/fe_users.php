<?php

declare(strict_types=1);
defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$tmp_slugField = [
    'slug' => [
        'exclude' => true,
        'label' => 'Slug',
        'config' => [
            'type' => 'slug',
            'generatorOptions' => [
                'fields' => ['username'],
                'fieldSeparator' => '-',
                //'prefixParentPageSlug' => true,
            ],
            'fallbackCharacter' => '-',
            'eval' => 'uniqueInSite',
        ],
    ],
];


$newColumns = [
    'birthday' => [
        'exclude' => true,
        'label' => 'Geburtsdatum',
        'config' => [
            'type' => 'input',
            'size' => 10,
            'eval' => 'trim',
            'placeholder' => 'TT-MM-YYYY',
        ],
    ],
    'admin' => [
        'exclude' => true,
        'label' => 'Administrator',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle', // optional, für hübsche Toggle-UI
            'default' => 0,                  // Standardwert = nicht aktiviert
        ],
    ],
    'description' => [
        'exclude' => true,
        'label' => 'Description',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'richtextConfiguration' => 'default',
            'rows' => 8,
        ],
    ],
    'profilbeschreibung' => [
        'exclude' => true,
        'label' => 'Profilbeschreibung',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'richtextConfiguration' => 'default',
            'rows' => 8,
        ],
    ],
    'signature' => [
        'exclude' => true,
        'label' => 'Signatur',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'richtextConfiguration' => 'default',
            'rows' => 8,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('fe_users', $newColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'birthday,admin',
    '',
    'after:name'
);

ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'profilbeschreibung,signature',
    '0',
    'after:email' // oder after:www / after:name
);

// 1️⃣ Füge das Feld zur TCA hinzu
ExtensionManagementUtility::addTCAcolumns('fe_users', $tmp_slugField);

// 2️⃣ Füge das Feld direkt nach "username" im Backend hinzu
ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'slug',
    '',
    'after:username'
);
