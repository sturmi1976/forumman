<?php

return [
    'frontend' => [
        'lanius/forumman/user-activity' => [
            'target' => \Lanius\Forumman\Middleware\FrontendUserActivityMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
