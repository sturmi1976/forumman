<?php

return [
    'ctrl' => [
        'title' => 'Forum Post / Topic',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'hideTable' => true,
        'searchFields' => 'title,content',
        'iconfile' => 'EXT:forumman/Resources/Public/Icons/post.svg',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
    ],
    'types' => [
        '1' => ['showitem' => '
            title, slug, content, forum, parent, user, hidden, created_at,
            --div--;Language, sys_language_uid, l10n_parent
        '],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'label' => 'Title / Topic',
            'config' => [
                'type' => 'input',
                'size' => 50,
            ],
        ],
        'slug' => [
            'exclude' => true,
            'label' => 'Slug',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '-',
                    //'prefixParentPageSlug' => true,
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
            ],
        ],
        'content' => [
            'exclude' => true,
            'label' => 'Content',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'cols' => 40,
                'rows' => 10,
            ],
        ],
        'created_at' => [
            'exclude' => true,
            'label' => 'Creation Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'forum' => [
            'exclude' => true,
            'label' => 'Forum',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    // optional: Default empty
                    ['', 0]
                ],
                'itemsProcFunc' => \Lanius\Forumman\Tca\ForumItemsProcFunc::class . '->main',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'user' => [
            'exclude' => true,
            'label' => 'Author (Frontend User)',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'minitems' => 0,
                'maxitems' => 1,
                //'readOnly' => true,
            ],
        ],
        'solved' => [
            'exclude' => true,
            'label' => 'Solved',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'parent' => [
            'exclude' => true,
            'label' => 'Parent Post (Reply)',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_forumman_domain_model_posts',
                'foreign_table_where' => 'AND tx_forumman_domain_model_posts.uid != ###THIS_UID### ORDER BY tx_forumman_domain_model_posts.title ASC',
                'minitems' => 0,
                'maxitems' => 1,
                'items' => [
                    ['Choose a parent post to reply to. Leave empty for a new thread.', 0],
                ],
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'Hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'Language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'default' => 0,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'Parent Post',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'tx_forumman_domain_model_posts',
                'foreign_table_where' => 'AND tx_forumman_domain_model_posts.pid=###CURRENT_PID### AND tx_forumman_domain_model_posts.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
