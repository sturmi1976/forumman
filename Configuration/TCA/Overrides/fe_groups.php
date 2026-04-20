<?php

defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$newColumns = [
    'group_color' => [
        'exclude' => true,
        'label' => 'Farbe für den Rang',
        'config' => [
            'type' => 'input',
            'renderType' => 'colorpicker',
            'size' => 10,
            'eval' => 'trim',
            'default' => '#000000',
        ],
    ],
    'admingroup' => [
        'exclude' => true,
        'label' => 'Administrator Gruppe',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
    'moderatorgroup' => [
        'exclude' => true,
        'label' => 'Moderatoren Gruppe',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('fe_groups', $newColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    'group_color,admingroup,moderatorgroup',
    '',
    'after:title'
);
