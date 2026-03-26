<?php

namespace Lanius\Forumman\ViewHelpers;

use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Extbase\Service\ImageService;


class MetaTagViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('property', 'string', 'Meta tag property (e.g. og:title)', false, '');
        $this->registerArgument('name', 'string', 'Meta tag name (e.g. description)', false, '');
        $this->registerArgument('content', 'string', 'Meta tag content', false);
        $this->registerArgument('image', 'mixed', 'FAL FileReference');
    }

    public function render(): void
    {
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
