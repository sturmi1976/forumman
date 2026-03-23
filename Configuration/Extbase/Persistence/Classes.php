<?php
return [
    \Lanius\Forumman\Domain\Model\Group::class => [
        'tableName' => 'fe_groups'
    ],
    \Lanius\Forumman\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
        'properties' => [
            'posts' => [
                'fieldName' => 'uid',
                'foreign_table' => 'tx_forumman_domain_model_posts',
                'foreign_field' => 'user'
            ],
            'txForummanLastActivity' => [
                'fieldName' => 'tx_forumman_last_activity'
            ]
        ]
    ],
    'Lanius\Forumman\Domain\Model\User' => [
        'tableName' => 'fe_users',
        'properties' => [
            'posts' => [
                'fieldName' => 'uid',
                'foreign_table' => 'tx_forumman_domain_model_posts',
                'foreign_field' => 'user'
            ],
            'userGroup' => [
                'mapOnProperty' => 'usergroup',
                'lazy' => true,
            ],
            'txForummanLastActivity' => [
                'fieldName' => 'tx_forumman_last_activity'
            ]
        ]
    ],
];
