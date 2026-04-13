<?php

declare(strict_types=1);

namespace Lanius\Forumman\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class ReindexService
{
    public function __construct(
        protected ElasticsearchService $elastic
    ) {}

    public function runBatch(int $offset, int $limit): array
    {
        $posts = $this->getPosts($offset, $limit);

        $count = 0;

        foreach ($posts as $post) {
            $this->elastic->index((string)$post['uid'], $post);
            $count++;
        }

        return [
            'count' => $count,
            'nextOffset' => $offset + $count,
            'finished' => $count < $limit
        ];
    }

    private function getPosts(int $offset, int $limit): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_forumman_domain_model_posts');

        return $connection->select(
            ['uid', 'title', 'content', 'user', 'username', 'forum', 'forumname', 'crdate'],
            'tx_forumman_domain_model_posts',
            [],
            [],
            ['uid' => 'ASC'],
            $limit,
            $offset
        )->fetchAllAssociative();
    }
}
