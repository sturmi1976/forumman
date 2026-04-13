<?php

namespace Lanius\Forumman\Backend\Toolbar;

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonBar;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;


class ElasticsearchToolbarItem implements ToolbarItemInterface
{
    // protected IconFactory $iconFactory;


    public function checkAccess(): bool
    {
        return true; // optional: Rechte prüfen
    }

    public function getItem(): string
    {

        // JS laden
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        //$pageRenderer->loadJavaScriptModule('@lanius/forumman/elasticsearch8.js');

        $pageRenderer->addJsFile(
            'EXT:forumman/Resources/Public/Js/elasticsearch.js',
            'text/javascript',
            false,
            false,
            '',
            true
        );


        $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('forumman');

        $enabled = (bool)($config['enableReindexButton'] ?? false);

        if (!$enabled) {
            return false;
        }


        return '
            <button id="es-reindex-button" class="btn btn-primary">
    Elasticsearch Reindex
</button>
        ';
    }

    public function getDropDown(): string
    {
        return ''; // kein Dropdown nötig
    }

    public function hasDropDown(): bool
    {
        return false;
    }

    public function getAdditionalAttributes(): array
    {
        return [];
    }

    public function getIndex(): int
    {
        return 90; // Position (neben Blitz ~ 50-60)
    }
}
