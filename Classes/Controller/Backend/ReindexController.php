<?php

namespace Lanius\Forumman\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Lanius\Forumman\Domain\Service\PostIndexer;
use Lanius\Forumman\Domain\Service\UserIndexer;
use TYPO3\CMS\Core\Database\ConnectionPool;

class ReindexController
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected PostIndexer $postIndexer,
        protected UserIndexer $userIndexer
    ) {}

    public function reindex(ServerRequestInterface $request): ResponseInterface
    {
        // 🔥 TEST
        // return new JsonResponse(['status' => 'ok']);

        // -----------------------------
        // POSTS
        // -----------------------------
        $connection = $this->connectionPool->getConnectionForTable('tx_forumman_domain_model_posts');

        $posts = $connection->select(
            ['*'],
            'tx_forumman_domain_model_posts',
            ['parent' => 0]
        )->fetchAllAssociative();

        foreach ($posts as $post) {
            $this->postIndexer->indexPost($post);
        }

        // -----------------------------
        // USERS
        // -----------------------------
        $userConnection = $this->connectionPool->getConnectionForTable('fe_users');

        $users = $userConnection->select(
            ['*'],
            'fe_users'
        )->fetchAllAssociative();

        foreach ($users as $user) {
            $this->userIndexer->indexUser($user);
        }

        return new JsonResponse([
            'finished' => true,
            'nextOffset' => count($posts) + count($users)
        ]);
    }
}
