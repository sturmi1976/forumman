<?php

declare(strict_types=1);

/*
 * This file is part of the package lanius/forumman.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lanius\Forumman\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

final class CategoriesRepository extends Repository
{
    public function initializeObject(): void
    {
        $defaultQuerySettings = $this->createQuery()->getQuerySettings();
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->defaultQuerySettings = $defaultQuerySettings;
    }


    /**
     * findAll sorted by sorting
     */
    public function findAllCategoriesAndForums()
    {
        $query = $this->createQuery();
        $query->setOrderings([
            'sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);
        return $query->execute();
    }
}
