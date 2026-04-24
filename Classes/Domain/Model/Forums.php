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
use Lanius\Forumman\Domain\Model\Posts;

final class Forums extends AbstractEntity
{
    //protected int $category = 0;
    protected string $description = '';
    protected string $title = '';
    // Kategorie als Objekt-Relation
    protected ?Categories $category = null;

    protected int $sorting = 0;

    protected int $threadCount = 0;
    protected int $postCount = 0;


    protected int $threadCountDynamic = 0;
    protected int $postCountDynamic = 0;

    protected ?ObjectStorage $posts = null;

    //protected ?\Lanius\Forumman\Domain\Model\Posts $latestPost = null;

    protected ?array $latestActivity = null;

    public function getLatestActivity(): ?array
    {
        return $this->latestActivity;
    }

    public function setLatestActivity(?array $latestActivity): void
    {
        $this->latestActivity = $latestActivity;
    }



    public function getLatestPost(): ?\Lanius\Forumman\Domain\Model\Posts
    {
        return $this->latestPost;
    }

    public function setLatestPost(?\Lanius\Forumman\Domain\Model\Posts $post): void
    {
        $this->latestPost = $post;
    }

    public function getThreadCountDynamic(): int
    {
        return $this->threadCountDynamic;
    }

    public function setThreadCountDynamic(int $count): void
    {
        $this->threadCountDynamic = $count;
    }

    public function getPostCountDynamic(): int
    {
        return $this->postCountDynamic;
    }

    public function setPostCountDynamic(int $count): void
    {
        $this->postCountDynamic = $count;
    }


    public function getThreadCount(): int
    {
        return $this->threadCount;
    }

    public function setThreadCount(int $threadCount): void
    {
        $this->threadCount = $threadCount;
    }

    public function getPostCount(): int
    {
        return $this->postCount;
    }

    public function setPostCount(int $postCount): void
    {
        $this->postCount = $postCount;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function getCategory(): ?Categories
    {
        return $this->category;
    }

    public function setCategory(?Categories $category): void
    {
        $this->category = $category;
    }


    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
