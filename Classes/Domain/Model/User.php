<?php

namespace Lanius\Forumman\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class User extends AbstractEntity
{
    protected string $username = '';

    public function getUsername(): string
    {
        return $this->username;
    }
}
