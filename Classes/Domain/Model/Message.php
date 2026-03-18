<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use Lanius\Forumman\Domain\Model\FrontendUser;

final class Message extends AbstractEntity
{
    protected string $subject = '';
    protected string $content = '';
    protected bool $isRead = false;
    protected ?FrontendUser $sender = null;
    protected ?FrontendUser $receiver = null;

    public function getSender(): ?FrontendUser
    {
        return $this->sender;
    }

    public function setSender(?FrontendUser $sender): void
    {
        $this->sender = $sender;
    }


    public function getReceiver(): ?FrontendUser
    {
        return $this->receiver;
    }

    public function setReceiver(?FrontendUser $receiver): void
    {
        $this->receiver = $receiver;
    }

    
    /**
 * @var \DateTime|null
 */
protected ?\DateTime $sentAt = null;

public function getSentAt(): ?\DateTime
{
    return $this->sentAt;
}

public function setSentAt(\DateTime $sentAt): void
{
    $this->sentAt = $sentAt;
}

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void 
    {
        $this->subject = $subject;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): void
    {
        $this->isRead = $isRead;
    }
}
