<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use Lanius\Forumman\Domain\Model\FrontendUser;
use Lanius\Forumman\Domain\Model\Forums;


final class Posts extends AbstractEntity
{
    protected string $title = '';
    protected string $content = '';
    protected int $category = 0;
    protected ?int $parent = null;
    protected bool $hidden = false;

    protected ?int $createdAt = null;

    protected ?Forums $forum = null;


    public function initializeObject(): void
    {
        if (!$this->createdAt || $this->createdAt === 0) {
            $this->createdAt = time();
        }
    }

    protected ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getForum(): ?Forums
    {
        return $this->forum;
    }

    public function setForum(?Forums $forum): void
    {
        $this->forum = $forum;
    }

    protected ?FrontendUser $user = null;

    public function getUser(): ?FrontendUser
    {
        return $this->user;
    }

    public function setUser(?FrontendUser $user): void
    {
        $this->user = $user;
    }


    public function getCreatedAt(): int
    {
        // Wenn 0 oder null, dann aktuellen Timestamp zurückgeben
        if (!$this->createdAt || $this->createdAt === 0) {
            return time();
        }

        return $this->createdAt;
    }

    public function setCreatedAt(?int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }


    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function setCategory(int $category): void
    {
        $this->category = $category;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(?int $parent): void
    {
        $this->parent = $parent;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }
}
