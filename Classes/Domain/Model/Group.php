<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

final class Group extends AbstractEntity
{
    protected string $title = '';
    protected ?string $groupColor = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getGroupColor(): ?string
    {
        return $this->groupColor;
    }

    public function setGroupColor(?string $color): void
    {
        $this->groupColor = $color;
    }
}
