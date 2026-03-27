<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'Private Nachrichten',
        'label' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'hideTable' => 0,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'hideTable' => true,
        'rootLevel' => 0,
        'searchFields' => 'subject,content',
        'iconfile' => 'EXT:forumman/Resources/Public/Icons/message.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'sender, receiver, subject, content, is_read, send_at',
    ],
    'types' => [
        '1' => ['showitem' => 'sender, receiver, subject, content, is_read, send_at'],
    ],
    'columns' => [
        'sender' => [
            'exclude' => 1,
            'label' => 'Absender',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'maxitems' => 1,
            ],
        ],
        'receiver' => [
            'exclude' => 1,
            'label' => 'Empfänger',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'maxitems' => 1,
            ],
        ],
        'subject' => [
            'exclude' => 0,
            'label' => 'Betreff',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,required',
            ],
        ],
        'content' => [
            'exclude' => 0,
            'label' => 'Nachricht',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
            ],
        ],
        'send_at' => [
            'label' => 'Sent at',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'is_read' => [
            'exclude' => 1,
            'label' => 'Gelesen',
            'config' => [
                'type' => 'check',
            ],
        ],
    ],
];
