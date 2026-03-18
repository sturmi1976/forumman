<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

final class PostsRepository extends Repository
{


    public function findRepliesByParent(int $parentUid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('parent', $parentUid))
            ->setOrderings(['createdAt' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING])
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


    public function findThreadsByForum(int $forumUid)
    {
        $query = $this->createQuery();

        // Einzelne Constraints erstellen
        $constraintForum = $query->equals('forum', $forumUid);
        $constraintParent = $query->equals('parent', 0);

        // logicalAnd mit Spread-Operator (funktioniert seit TYPO3 v12+)
        $query->matching(
            $query->logicalAnd(
                $constraintForum,
                $constraintParent
            )
        );

        // Sortierung nach Erstellungsdatum absteigend
        $query->setOrderings([
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
}
