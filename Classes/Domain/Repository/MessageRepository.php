<?php

declare(strict_types=1);

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

final class MessageRepository extends Repository
{
    /**
     * Nachrichten für einen bestimmten Receiver holen
     */
    public function findByReceiver(int $receiverUid)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('receiver', $receiverUid)
        );
        $query->setOrderings(['send_at' => 'DESC']);
        return $query->execute();
    }
}
