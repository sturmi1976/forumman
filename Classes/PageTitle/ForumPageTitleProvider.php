<?php

declare(strict_types=1);

namespace Lanius\Forumman\PageTitle;

use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

final class ForumPageTitleProvider extends AbstractPageTitleProvider
{
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
