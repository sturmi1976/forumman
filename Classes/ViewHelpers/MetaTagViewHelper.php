<?php

namespace Lanius\Forumman\ViewHelpers;

use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Extbase\Service\ImageService;

use TYPO3\CMS\Core\Page\PageRenderer;


class MetaTagViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('property', 'string', 'Meta tag property (e.g. og:title)', false, '');
        $this->registerArgument('name', 'string', 'Meta tag name (e.g. description)', false, '');
        $this->registerArgument('content', 'string', 'Meta tag content', false);
        $this->registerArgument('image', 'mixed', 'FAL FileReference');
        $this->registerArgument('canonical', 'string', 'Canonical URL', false, '');
    }

    public function render(): void
    {
        $canonical = $this->arguments['canonical'];

        if (!empty($this->arguments['canonical'])) {

            $canonical = trim($this->arguments['canonical']);

            /** @var PageRenderer $pageRenderer */
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

            $pageRenderer->addHeaderData(
                '<link rel="canonical" href="' . htmlspecialchars($canonical) . '" />'
            );

            return;
        }


        $property = $this->arguments['property'];
        $name = $this->arguments['name'];
        $content = $this->arguments['content'];
        $image = $this->arguments['image'];

        if ($image) {
            /** @var ImageService $imageService */
            $imageService = GeneralUtility::makeInstance(ImageService::class);
            $imageObject = $imageService->getImage('', $image, false);
            $content = $imageService->getImageUri($imageObject, true); // absolute URL
        }

        if (is_string($content)) {
            // HTML-Tags entfernen (falls nicht schon passiert)
            $content = strip_tags($content);

            // Zeilenumbrüche entfernen
            $content = str_replace(["\r", "\n"], ' ', $content);

            // Mehrfachen Whitespace reduzieren
            $content = preg_replace('/\s+/', ' ', $content);

            // Trim
            $content = trim($content);
        }

        if (empty($content)) {
            return;
        }

        /** @var MetaTagManagerRegistry $registry */
        $registry = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);

        if (!empty($property)) {
            $manager = $registry->getManagerForProperty($property);
            $manager->addProperty($property, $content);
        } elseif (!empty($name)) {
            $manager = $registry->getManagerForProperty($name);
            $manager->addProperty($name, $content);
        }
    }
}
