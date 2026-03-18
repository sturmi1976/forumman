<?php

declare(strict_types=1);

namespace Lanius\Forumman\Tca;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Lanius\Forumman\Domain\Repository\CategoriesRepository;

class ForumItemsProcFunc
{
    public function main(array &$config): void
    {
        /** @var CategoriesRepository $categoriesRepository */
        $categoriesRepository = GeneralUtility::makeInstance(CategoriesRepository::class);

        $items = [];

        $categories = $categoriesRepository->findAll();

        foreach ($categories as $category) {
            // Kategorie fett-ähnlich darstellen, nicht auswählbar
            $items[] = ['▶ ' . strtoupper($category->getTitle()), 0];

            // Foren in dieser Kategorie
            foreach ($category->getForums() as $forum) {
                $items[] = ['   ' . $forum->getTitle(), (int)$forum->getUid()]; // eingerückt
            }
        }

        $config['items'] = $items;

        // Verhindert, dass Value = 0 gespeichert wird
        $config['disableNoMatchingValueCheck'] = true;
    }
}
