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
        $this->registerArgument('title', 'string', 'Page title', false, '');
        $this->registerArgument('prefix', 'string', 'Prefix for title', false, '');
        $this->registerArgument('suffix', 'string', 'Suffix for title', false, '');
    }

    public function render(): string
    {
        $title  = $this->arguments['title'] ?? '';
        $prefix = $this->arguments['prefix'] ?? '';
        $suffix = $this->arguments['suffix'] ?? '';

        // Titel zusammensetzen
        $fullTitle = trim(
            ($prefix ? $prefix . ' ' : '') .
                $title .
                ($suffix ? ' ' . $suffix : '')
        );

        $this->titleProvider->setTitle($fullTitle);

        return '';
    }
}
