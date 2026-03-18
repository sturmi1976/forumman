<?php

declare(strict_types=1);

namespace Lanius\Forumman\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class LanguageKeyViewHelper extends AbstractViewHelper
{
    public function render(): string
    {
        // Default fallback
        $languageKey = 'en';

        /** @var ServerRequestInterface|null $request */
        $request = $this->renderingContext
            ->getAttribute(ServerRequestInterface::class);

        if ($request instanceof ServerRequestInterface) {
            /** @var SiteLanguage|null $language */
            $language = $request->getAttribute('language');

            if ($language instanceof SiteLanguage) {
                /** @var Locale $locale */
                $locale = $language->getLocale();
                $languageKey = $locale->getLanguageCode();
            }
        }

        return $languageKey;
    }
}
