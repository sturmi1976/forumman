<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Service;

use Lanius\Forumman\Service\ElasticsearchService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class UserIndexer
{
    public function __construct(
        protected ElasticsearchService $elasticsearchService
    ) {}

    public function indexUser(array $user): void
    {
        $postCount = $this->getPostCountByUser((int)$user['uid']);
        $usergroup = $this->getUsergroupByUid((int)$user['usergroup']);

        //DebuggerUtility::var_dump($user); 


        $this->elasticsearchService->indexUser(
            (string)$user['uid'],
            [
                'uid' => (int)$user['uid'],
                'username' => $user['username'] ?? '',
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'city' => $user['city'] ?? '',
                'www' => $user['www'] ?? '',
                'profilbeschreibung' => $user['profilbeschreibung'] ?? '',
                'company' => $user['company'] ?? '',
                'crdate' => $user['crdate'] ?? time(),
                'postCount' => $postCount,
                'usergroup' => $usergroup,
            ]
        );
    }



    public function deleteUser(int $uid): void
    {
        $this->elasticsearchService->delete2((string)$uid);
    }


    protected function getPostCountByUser(int $userUid): int
    {
        if ($userUid <= 0) {
            return 0;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_forumman_domain_model_posts');

        return (int)$connection->count(
            '*',
            'tx_forumman_domain_model_posts',
            [
                'user' => $userUid,
                //'parent' => 0 // optional: nur Hauptposts
            ]
        );
    }



    protected function getUsergroupByUid(int $uid): string
    {
        if ($uid <= 0) {
            return '-';
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_groups');

        $row = $connection->select(
            ['title'],
            'fe_groups',
            ['uid' => $uid]
        )->fetchAssociative();

        return $row['title'] ?? '-';
    }
}
