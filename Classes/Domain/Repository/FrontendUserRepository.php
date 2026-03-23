<?php

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;


final class FrontendUserRepository extends Repository
{
    /**
     * @var ConnectionPool
     */
    protected ConnectionPool $connectionPool;

    /**
     * @param ConnectionPool $connectionPool
     */
    public function injectConnectionPool(ConnectionPool $connectionPool): void
    {
        $this->connectionPool = $connectionPool;
    }


    /**
     * Prüft, ob ein User aktuell online ist
     */
    public function isUserOnline(int $userUid, int $timeout = 300): bool
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $result = $queryBuilder
            ->select('is_online')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($userUid)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!$result || empty($result['is_online'])) {
            return false;
        }

        $lastActivity = (int)$result['is_online'];

        return $lastActivity >= (time() - $timeout);
    }



    public function findOnlineUsers(int $minutes = 10): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        $activeSince = time() - ($minutes * 60);

        $rows = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('disable', 0),
                $queryBuilder->expr()->gt('tx_forumman_last_activity', $activeSince)
            )
            ->orderBy('tx_forumman_last_activity', 'DESC')
            ->fetchAllAssociative();

        return $rows;
    }
}
