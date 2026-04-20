<?php

declare(strict_types=1);

namespace Lanius\Forumman\Seo\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

#[AsEventListener(
    identifier: 'forumman/canonical'
)]
final readonly class CanonicalListener
{
    public function __invoke(ModifyUrlForCanonicalTagEvent $event): void
    {
        // optional: wenn TYPO3 bewusst deaktiviert hat
        if ($event->getCanonicalGenerationDisabledException()) {
            return;
        }

        $uri = $event->getRequest()->getUri();

        // WICHTIG: immer absolute URL erzwingen
        $canonical = (string)$uri->withQuery('')->withFragment('');

        $event->setUrl($canonical);
    }
}
