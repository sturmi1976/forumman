<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Service;

use Lanius\Forumman\Service\ElasticsearchService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PostIndexer
{
    public function __construct(
        protected ElasticsearchService $elasticsearchService
    ) {}

    public function indexPost(array $post): void
    {

        $username = $this->getUsernameByUid((int)$post['user']);
        $forum = $this->getForumByUid((int)$post['forum']);

        $this->elasticsearchService->index(
            (string)$post['uid'],
            [
                'uid' => (int)$post['uid'],
                'forum' => (int)($post['forum'] ?? 0),
                'forumname' => $forum,
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
                'user' => (int)$post['user'],
                'username' => $username,
                'crdate' => $post['crdate'],

            ]
        );
    }





    public function deletePost(int $uid): void
    {
        $this->elasticsearchService->delete((string)$uid);
    }

    protected function getUsernameByUid(int $uid): string
    {
        if ($uid <= 0) {
            return 'guest';
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $row = $connection->select(
            ['username'],
            'fe_users',
            ['uid' => $uid]
        )->fetchAssociative();

        return $row['username'] ?? 'unknown';
    }



    protected function getForumByUid(int $uid): string
    {
        if ($uid <= 0) {
            return 'guest';
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_forumman_domain_model_forums');

        $row = $connection->select(
            ['title'],
            'tx_forumman_domain_model_forums',
            ['uid' => $uid]
        )->fetchAssociative();

        return $row['title'] ?? 'unknown';
    }
}
