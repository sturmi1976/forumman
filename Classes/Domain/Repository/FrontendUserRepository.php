<?php

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;


class FrontendUserRepository extends Repository
{
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
}
