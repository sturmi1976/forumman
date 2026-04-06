<?php

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class StatisticsRepository
{
    public function getUserCount(): int
    {
        return $this->countTable('fe_users');
    }

    /**
     * Threads = Posts mit parent = 0
     */
    public function getThreadCount(): int
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        return (int)$qb
            ->count('*')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $qb->expr()->eq('parent', 0)
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Alle Posts (Threads + Replies)
     */
    public function getPostCount(): int
    {
        return $this->countTable('tx_forumman_domain_model_posts');
    }

    /**
     * Nur Replies (parent > 0)
     */
    public function getReplyCount(): int
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        return (int)$qb
            ->count('*')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $qb->expr()->gt('parent', 0)
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Posts heute
     */
    public function getPostsToday(): int
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        return (int)$qb
            ->count('*')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $qb->expr()->gte(
                    'crdate',
                    $qb->createNamedParameter(strtotime('today midnight'))
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Helper: einfache Count-Funktion
     */
    private function countTable(string $table): int
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        return (int)$qb
            ->count('*')
            ->from($table)
            ->executeQuery()
            ->fetchOne();
    }



    public function getTopThreads(int $limit = 5, int $languageId = 0): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        $qb->select('p.uid', 'p.title', 'p.forum')
            ->addSelectLiteral('COUNT(r.uid) as replyCount')
            ->from('tx_forumman_domain_model_posts', 'p')
            ->leftJoin(
                'p',
                'tx_forumman_domain_model_posts',
                'r',
                $qb->expr()->eq('r.parent', 'p.uid')
            )
            ->where(
                $qb->expr()->eq('p.parent', 0),
                $qb->expr()->eq('p.sys_language_uid', (int)$languageId)
            )
            ->groupBy('p.uid')
            ->orderBy('replyCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }


    public function getPostsLast7Days(int $limit = 3): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        $qb->selectLiteral('DATE(FROM_UNIXTIME(crdate)) as day')
            ->addSelectLiteral('COUNT(*) as count')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $qb->expr()->gte(
                    'crdate',
                    strtotime('-7 days')
                )
            )
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }



    public function getTopUsers(int $limit = 5): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        $qb->select('u.uid', 'u.username')
            ->addSelectLiteral('COUNT(p.uid) as postCount')
            ->from('tx_forumman_domain_model_posts', 'p')
            ->leftJoin(
                'p',
                'fe_users',
                'u',
                $qb->expr()->eq('p.user', 'u.uid')
            )
            ->groupBy('u.uid')
            ->orderBy('postCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }
}
