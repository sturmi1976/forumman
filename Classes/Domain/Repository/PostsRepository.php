<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Lanius\Forumman\Domain\Model\Posts;
use TYPO3\CMS\Core\Database\ConnectionPool;

use TYPO3\CMS\Core\Database\Connection;
use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;


final class PostsRepository extends Repository
{

    public function initializeObject()
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findLatestPostByForum(int $forumUid)
    {
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        $row = $queryBuilder
            ->select('*')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $queryBuilder->expr()->eq(
                    'forum',
                    $queryBuilder->createNamedParameter($forumUid)
                ),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0)
            )
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        //\TYPO3\CMS\Core\Utility\DebugUtility::debug($row);
        //die();

        if (!$row) {
            return null;
        }

        // 👉 DAS ist der entscheidende Teil:
        return $this->findByUid((int)$row['uid']);
    }





    public function findLatestActivityByForum(int $forumUid): ?array
    {
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        $row = $queryBuilder
            ->select('uid')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $queryBuilder->expr()->eq(
                    'forum',
                    $queryBuilder->createNamedParameter($forumUid)
                ),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0)
            )
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        // 🔥 WICHTIG: Wenn kein Post existiert → sofort raus
        if (!$row) {
            return null;
        }

        $latestPost = $this->findByUid((int)$row['uid']);

        // 🔥 ZUSÄTZLICHE ABSICHERUNG
        if (!$latestPost) {
            return null;
        }

        // 🔥 JETZT ERST getParent() verwenden!
        if ($latestPost->getParent() && $latestPost->getParent() > 0) {
            $thread = $this->findByUid($latestPost->getParent());
        } else {
            $thread = $latestPost;
        }

        return [
            'post' => $latestPost,
            'thread' => $thread,
        ];
    }



    public function updateEdit(int $postId, string $content): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_forumman_domain_model_posts');

        $affectedRows = $connection->update(
            'tx_forumman_domain_model_posts',
            [
                'content' => $content,
                'tstamp' => time()
            ],
            [
                'uid' => $postId
            ]
        );

        return $affectedRows > 0;
    }


    public function findRepliesByParent(int $parentUid, int $languageUid)
    {

        $query = $this->createQuery();

        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('parent', $parentUid),
                    $query->equals('sys_language_uid', $languageUid)
                )
            )
            ->setOrderings([
                'createdAt' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
            ])
            ->execute();
    }



    public function findReplies(int $parentUid)
    {
        $query = $this->createQuery();
        return $query
            ->matching(
                $query->equals('parent', $parentUid)
            )
            ->execute();
    }


    public function findThreadsByForum(int $forumUid, int $languageId)
    {
        $query = $this->createQuery();

        // Einzelne Constraints erstellen
        $constraintForum = $query->equals('forum', $forumUid);
        $constraintParent = $query->equals('parent', 0);
        $languageId = $query->equals('sys_language_uid', $languageId);

        // logicalAnd mit Spread-Operator (funktioniert seit TYPO3 v12+)
        $query->matching(
            $query->logicalAnd(
                $constraintForum,
                $constraintParent,
                $languageId
            )
        );

        // Sortierung nach Erstellungsdatum absteigend
        $query->setOrderings([
            'isAdminNotice' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
            'crdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
        ]);

        return $query->execute();
    }

    public function countThreadsByForum(int $forumUid): int
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('forum', $forumUid),
                $query->equals('parent', 0)
            )
        );

        return $query->count();
    }


    public function findOneBySlug(string $slug)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('slug', $slug)
        );
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }


    /**
     * Count all posts for a given user
     */
    public function countByUser(int $userUid): int
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('user', $userUid)
        );

        return $query->execute()->count();
    }


    public function setLanguageUidForPost(int $postId, int $languageId): void
    {
        if ($postId <= 0) {
            return; // Absicherung: ungültige Post-ID
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_forumman_domain_model_posts');

        $connection->update(
            'tx_forumman_domain_model_posts',
            ['sys_language_uid' => $languageId],
            ['uid' => $postId]
        );
    }


    public function countRepliesByParent(int $parentUid, int $languageUid): int
    {
        $query = $this->createQuery();

        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('parent', $parentUid),
                    $query->equals('sys_language_uid', $languageUid)
                )
            )
            ->count();
    }


    public function countPostsByForum(int $forumUid): int
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->equals('forum', $forumUid)
        )->count();
    }


    public function countRepliesByThread(int $threadUid): int
    {
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        return (int)$queryBuilder
            ->count('uid')
            ->from('tx_forumman_domain_model_posts')
            ->where(
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($threadUid)
                ),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0)
            )
            ->executeQuery()
            ->fetchOne();
    }



    /* Ähnliche Themen */
    public function findSimilarThreads(int $postUid, int $languageId, int $limit = 5): array
    {
        $currentPost = $this->findByUid($postUid);
        if (!$currentPost) {
            return [];
        }

        $text = trim($currentPost->getTitle() . ' ' . strip_tags($currentPost->getContent()));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        $words = preg_split('/\s+/', $text);

        $booleanSearch = '';

        foreach ($words as $word) {
            $word = trim($word);

            if (mb_strlen($word) < 3) {
                continue;
            }

            if (in_array(mb_strtolower($word), [
                'und',
                'oder',
                'der',
                'die',
                'das',
                'ein',
                'eine',
                'was',
                'wie',
                'ist'
            ])) {
                continue;
            }

            $booleanSearch .= $word . '* ';
        }

        $booleanSearch = trim($booleanSearch);

        if ($booleanSearch === '') {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_forumman_domain_model_posts');

        $scoreExpr = 'MATCH(p.title, p.content) AGAINST (:search IN BOOLEAN MODE)';

        $rows = $queryBuilder
            ->select(
                'p.uid',
                'p.title',
                'p.content',
                'p.tstamp',
                'p.created_at',
                'p.forum',
                'p.user',
                'p.sys_language_uid',
                'p.is_admin_notice',

                // 👉 User Felder direkt mitladen
                'u.uid AS user_uid',
                'u.username AS user_username',
                'u.name AS user_name',
                'u.email AS user_email'
            )
            ->addSelectLiteral($scoreExpr . ' AS score')
            ->from('tx_forumman_domain_model_posts', 'p')

            // 👉 JOIN auf fe_users
            ->leftJoin(
                'p',
                'fe_users',
                'u',
                $queryBuilder->expr()->eq('p.user', $queryBuilder->quoteIdentifier('u.uid'))
            )

            ->where(
                $queryBuilder->expr()->eq('p.parent', 0),
                $queryBuilder->expr()->neq('p.uid', $queryBuilder->createNamedParameter($postUid)),
                $queryBuilder->expr()->eq('p.deleted', 0),
                $queryBuilder->expr()->eq('p.hidden', 0),
                $queryBuilder->expr()->eq('p.sys_language_uid', $languageId),
                $queryBuilder->expr()->eq('p.is_admin_notice', 0)
            )
            ->setParameter('search', $booleanSearch)

            ->having('score > 3')
            ->orderBy('score', 'DESC')
            ->addOrderBy('p.created_at', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        // 👉 User strukturieren
        foreach ($rows as &$row) {
            $row['user'] = $row['user_uid'] ? [
                'uid' => (int)$row['user_uid'],
                'username' => $row['user_username'],
                'name' => $row['user_name'],
                'email' => $row['user_email'],
            ] : null;
            $row['forum'] = (int)$row['forum'];

            unset(
                $row['user_uid'],
                $row['user_username'],
                $row['user_name'],
                $row['user_email']
            );
        }

        return $rows ?: [];
    }



    public function findByUids(array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $queryBuilder = $connectionPool->getQueryBuilderForTable('fe_users');

        $rows = $queryBuilder
            ->select('uid', 'username')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $uids,
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('disable', 0)
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows ?: [];
    }
}
