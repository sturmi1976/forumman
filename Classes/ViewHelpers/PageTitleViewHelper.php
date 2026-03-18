<?php

declare(strict_types=1);

namespace Lanius\Forumman\ViewHelpers;

use Lanius\Forumman\PageTitle\ForumPageTitleProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class PageTitleViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function __construct(
        private readonly ForumPageTitleProvider $titleProvider
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('title', 'string', 'Page title', true);
    }

    public function render(): string
    {
        $title = $this->arguments['title'];

        $this->titleProvider->setTitle($title);

        return '';
    }
}
