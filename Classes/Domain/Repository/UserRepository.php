<?php

declare(strict_types=1);

/*
 * This file is part of the package lanius/forum.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;


final class UserRepository extends Repository
{

    public function insertUser(array $insertArray): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        // Passwort hashen!
        $hashedPassword = password_hash($insertArray['password1'], PASSWORD_DEFAULT);

        $queryBuilder
            ->insert('fe_users')
            ->values([
                'pid' => $insertArray['pid'],
                'tstamp' => time(),
                'crdate' => time(),
                'username' => $insertArray['username'],
                'slug' => $insertArray['slug'],
                'password' => $hashedPassword,
                'email' => $insertArray['email'],
                'usergroup' => 1,
                'disable' => 1,
                'md5_hash' => $insertArray['md5_hash']
            ])
            ->executeStatement();

        return (int)$queryBuilder->getConnection()->lastInsertId();
    }



    /**
     * Setzt den FE-User auf offline
     */
    public function setUserOffline(int $userId): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->update(
            'fe_users',
            ['is_online' => 0], // neues Timestamp setzen
            ['uid' => $userId]       // WHERE uid = $userId
        );
    }


    public function emailExists(string $email): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $count = $queryBuilder
            ->count('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($email))
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count > 0;
    }



    public function usernameExists(string $username): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $count = $queryBuilder
            ->count('uid')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'username',
                    $queryBuilder->createNamedParameter(trim($username))
                )
            )
            ->fetchOne();

        return (int)$count > 0;
    }



    public function findUserByMd5(string $md5): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('md5_hash', $queryBuilder->createNamedParameter($md5))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }

    public function activateUser(int $uid): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $queryBuilder
            ->update('fe_users')
            ->set('disable', 0)
            ->set('md5_hash', '')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid))
            )
            ->executeStatement();
    }



    public function findUserByUid($uid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $row = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }


    public function findUserByUid2(int $uid): ?int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $row = $queryBuilder
            ->select('uid')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid))
            )
            ->executeQuery()
            ->fetchOne();

        return $row ?: null;
    }


    public function findUserGroupById($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_groups');

        $row = $queryBuilder
            ->select('*')
            ->from('fe_groups')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: [];
    }


    public function findUserImage($uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $row = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('fe_users')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('image')
                ),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uid)
                )
            )
            ->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()          // 👈 korrekt
            ->fetchAssociative();     // 👈 genau 1 Datensatz

        return $row ?: [];
    }




    public function findUserImage2(array $user): ?array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_reference');

        $qb = $connection->createQueryBuilder();

        $row = $qb
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $qb->expr()->eq('tablenames', $qb->createNamedParameter('fe_users')),
                $qb->expr()->eq('fieldname', $qb->createNamedParameter('image')),
                $qb->expr()->eq('uid_foreign', $qb->createNamedParameter((int)$user['uid'])),
                $qb->expr()->eq('deleted', 0),
                $qb->expr()->eq('hidden', 0)
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }




    /**
     * Get all FE users who were active in the last X minutes
     *
     * @param int $minutes Default 10
     * @return array<int, int> Array mit User-IDs als Key und Timestamp als Value
     */
    public function findOnlineUsers(int $minutes = 10): array
    {
        $threshold = time() - ($minutes * 60);

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $rows = $connection->createQueryBuilder()
            ->select('uid', 'is_online', 'username')
            ->from('fe_users')
            ->where('is_online > :threshold')
            ->setParameter('threshold', $threshold)
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        $i = 0;
        foreach ($rows as $row) {
            $result[$i] = [
                'uid'       => (int)$row['uid'],
                'username'  => ucfirst(htmlspecialchars($row['username'])),
                'is_online' => (int)$row['is_online']
            ];
            $i++;
        }


        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($result);

        return $result;
    }



    /**
     * Update 'is_online' timestamp for a given FE user
     *
     * @param int $userId FE User UID
     * @return void
     */
    public function updateOnlineTimestamp(int $userId): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->update(
            'fe_users',
            ['is_online' => time()], // neues Timestamp setzen
            ['uid' => $userId]       // WHERE uid = $userId
        );
    }




    /**
     * Liefert die letzten X registrierten FE-Users
     *
     * @param int $limit
     * @return array
     */
    public function findLastRegisteredUsers(int $limit = 3): array
    {
        // Connection holen
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $qb = $connection->createQueryBuilder();

        $rows = $qb
            ->select('*')
            ->from('fe_users')
            ->where(
                $qb->expr()->eq('deleted', 0),
                $qb->expr()->eq('disable', 0)
            )
            ->orderBy('crdate', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }




    /**
     * Update FE-User Profil
     */
    public function updateFrontendUser(
        int $userUid,
        string $company,
        string $name,
        string $profileDescription,
        string $www,
        string $signatur,

    ): void {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->update(
            'fe_users',
            [
                'company' => $company,
                'name' => $name,
                'profilbeschreibung' => $profileDescription,
                'tstamp' => time(),
                'www' => $www,
                'signature' => $signatur
            ],
            [
                'uid' => $userUid,
                'deleted' => 0,
                'disable' => 0,
            ]
        );
    }



    public function findLastLoggedInUsers(int $limit = 3): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        return $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->gt('lastlogin', 0),
                $queryBuilder->expr()->eq('disable', 0),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('lastlogin', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
