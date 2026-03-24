<?php

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use Lanius\Forumman\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Core\Resource\FileRepository;

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


    public function findLastLoggedInUsersObjects(int $limit = 3): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $rows = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->gt('lastlogin', 0)
            )
            ->orderBy('lastlogin', 'DESC')
            ->setMaxResults($limit)
            ->fetchAllAssociative();

        $users = [];
        /** @var GroupRepository $groupRepository */
        $groupRepository = GeneralUtility::makeInstance(GroupRepository::class);

        foreach ($rows as $row) {
            /** @var FrontendUser $user */
            $user = GeneralUtility::makeInstance(FrontendUser::class);
            $user->_setProperty('uid', (int)$row['uid']);
            $user->_setProperty('username', ucfirst($row['username']));
            $user->_setProperty('birthday', $row['birthday']);
            $user->_setProperty('slug', $row['slug']);
            $user->_setProperty('lastlogin', (int)$row['lastlogin']);

            if (!empty($row['birthday'])) {
                $birthDate = new \DateTime($row['birthday']);
                $today = new \DateTime('today');
                $age = $birthDate->diff($today)->y;
                $user->_setProperty('age2', $age);
            }


            // --- Gruppen auflösen ---
            $usergroupStorage = new ObjectStorage();
            if (!empty($row['usergroup']) && is_string($row['usergroup'])) {
                $uids = GeneralUtility::intExplode(',', $row['usergroup'], true);
                foreach ($uids as $uid) {
                    $group = $groupRepository->findByUid($uid);
                    if ($group !== null) {
                        $usergroupStorage->attach($group);
                    }
                }
            }
            $user->_setProperty('usergroup', $usergroupStorage);

            // --- FAL-Bild laden ---
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

            $files = $fileRepository->findByRelation('fe_users', 'image', (int)$row['uid']);

            if (!empty($files)) {
                /** @var \TYPO3\CMS\Core\Resource\FileReference $file */
                $file = reset($files); // erstes Bild
                $extbaseFile = GeneralUtility::makeInstance(ExtbaseFileReference::class);
                $extbaseFile->_setProperty('originalResource', $file);
                $user->_setProperty('image', $extbaseFile);
            }

            $users[] = $user;
        }

        return $users;
    }






    public function findNewUsersObjects(int $limit = 3): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $rows = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->gt('uid', 0)
            )
            ->orderBy('uid', 'DESC')
            ->setMaxResults($limit)
            ->fetchAllAssociative();


        $users = [];
        /** @var GroupRepository $groupRepository */
        $groupRepository = GeneralUtility::makeInstance(GroupRepository::class);

        foreach ($rows as $row) {
            /** @var FrontendUser $user */
            $user = GeneralUtility::makeInstance(FrontendUser::class);
            $user->_setProperty('uid', (int)$row['uid']);
            $user->_setProperty('username', ucfirst($row['username']));
            $user->_setProperty('birthday', $row['birthday']);
            $user->_setProperty('slug', $row['slug']);
            $user->_setProperty('lastlogin', (int)$row['lastlogin']);

            if (!empty($row['birthday'])) {
                $birthDate = new \DateTime($row['birthday']);
                $today = new \DateTime('today');
                $age = $birthDate->diff($today)->y;
                $user->_setProperty('age2', $age);
            }



            $usergroupStorage = new ObjectStorage();
            if (!empty($row['usergroup']) && is_string($row['usergroup'])) {
                $uids = GeneralUtility::intExplode(',', $row['usergroup'], true);
                foreach ($uids as $uid) {
                    $group = $groupRepository->findByUid($uid);
                    if ($group !== null) {
                        $usergroupStorage->attach($group);
                    }
                }
            }
            $user->_setProperty('usergroup', $usergroupStorage);

            // --- FAL-Image load ---
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

            $files = $fileRepository->findByRelation('fe_users', 'image', (int)$row['uid']);

            if (!empty($files)) {
                /** @var \TYPO3\CMS\Core\Resource\FileReference $file */
                $file = reset($files); // erstes Bild
                $extbaseFile = GeneralUtility::makeInstance(ExtbaseFileReference::class);
                $extbaseFile->_setProperty('originalResource', $file);
                $user->_setProperty('image', $extbaseFile);
            }

            $users[] = $user;
        }

        return $users;
    }
}
