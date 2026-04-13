<?php

return [
    'forumman_reindex' => [
        'path' => '/forumman/elasticsearch/reindex',
        'target' => \Lanius\Forumman\Controller\Backend\ReindexController::class . '::reindex'
    ],
];
