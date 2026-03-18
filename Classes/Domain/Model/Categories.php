<?php

declare(strict_types=1);

/*
 * This file is part of the package lanius/forumman.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lanius\Forumman\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Annotation\ORM\OrderBy;
use Lanius\Forumman\Domain\Model\Forums;

final class Categories extends AbstractEntity
{

    /**
     * Forums relation
     *
     * @var ObjectStorage<Forums>
     */
    #[OrderBy(['sorting' => 'ASC'])]
    protected ObjectStorage $forums;

    protected string $title = '';

    public function __construct()
    {
        $this->forums = new ObjectStorage();
    }

    /**
     * Original ObjectStorage (unsorted)
     */
    public function getForums(): ObjectStorage
    {
        return $this->forums;
    }

    public function setForums(ObjectStorage $forums): void
    {
        $this->forums = $forums;
    }

    public function addForum(Forums $forum): void
    {
        $this->forums->attach($forum);
    }

    public function removeForum(Forums $forum): void
    {
        $this->forums->detach($forum);
    }


    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
