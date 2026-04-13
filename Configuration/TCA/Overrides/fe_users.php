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
    /*
    'usergroup' => [
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'fe_groups',
            'MM' => 'fe_users_usergroup_mm',
            'size' => 10,
            'autoSizeMax' => 30,
            'maxitems' => 9999,
            'multiple' => 0,
        ],
    ],*/
    'admin' => [
        'exclude' => true,
        'label' => 'Administrator',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
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
    'tx_forumman_last_activity' => [
        'exclude' => true,
        'label' => 'Last Activity (Forum)',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputDateTime',
            'eval' => 'datetime,int',
            'default' => 0,
            'readOnly' => true,
        ],
    ],
    'show_age' => [
        'exclude' => true,
        'label' => 'Show Age',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],

    'facebooklink' => [
        'exclude' => true,
        'label' => 'Facebook',
        'config' => [
            'type' => 'input',
            'size' => 100,
            'eval' => 'trim',
            'placeholder' => '',
        ],
    ],
    'twitterlink' => [
        'exclude' => true,
        'label' => 'Twitter / X',
        'config' => [
            'type' => 'input',
            'size' => 100,
            'eval' => 'trim',
            'placeholder' => '',
        ],
    ],
    'linkedinlink' => [
        'exclude' => true,
        'label' => 'LinkedIn',
        'config' => [
            'type' => 'input',
            'size' => 100,
            'eval' => 'trim',
            'placeholder' => '',
        ],
    ],
    'instagramlink' => [
        'exclude' => true,
        'label' => 'Instagram',
        'config' => [
            'type' => 'input',
            'size' => 100,
            'eval' => 'trim',
            'placeholder' => '',
        ],
    ],
    'youtubelink' => [
        'exclude' => true,
        'label' => 'YouTube',
        'config' => [
            'type' => 'input',
            'size' => 100,
            'eval' => 'trim',
            'placeholder' => '',
        ],
    ],
    'xinglink' => [
        'exclude' => true,
        'label' => 'Xing',
        'config' => [
            'type' => 'input',
            'size' => 100,
            'eval' => 'trim',
            'placeholder' => '',
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('fe_users', $newColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'birthday,admin,show_age',
    '',
    'after:name'
);

ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--div--;Links,facebooklink,twitterlink,linkedinlink,instagramlink,youtubelink,xinglink',
    '',
    ''
);

ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--div--;Profiltext und Signatur,profilbeschreibung,signature',
    '',
    ''
);


ExtensionManagementUtility::addTCAcolumns('fe_users', $tmp_slugField);


ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'slug',
    '',
    'after:username'
);
