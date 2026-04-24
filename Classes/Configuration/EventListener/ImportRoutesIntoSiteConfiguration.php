<?php

declare(strict_types=1);

namespace Lanius\Forumman\Configuration\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

#[AsEventListener(
    identifier: 'forumman/import-routes-into-site-configuration',
)]
final readonly class ImportRoutesIntoSiteConfiguration
{
    private const ROUTES = 'EXT:forumman/Configuration/Routes/Forum.yaml';
    private const ROUTES2 = 'EXT:forumman/Configuration/Routes/Userlist.yaml';

    public function __construct(
        private YamlFileLoader $yamlFileLoader,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function __invoke(SiteConfigurationLoadedEvent $event): void
    {
        $extConf = $this->extensionConfiguration->get('forumman');
        $enableRouteImport = (bool)($extConf['enableRouteImport'] ?? true);


        if ($enableRouteImport) {
            $routeConfiguration = $this->yamlFileLoader->load(self::ROUTES);
            $routeConfiguration2 = $this->yamlFileLoader->load(self::ROUTES2);

            $siteConfiguration = $event->getConfiguration();

            ArrayUtility::mergeRecursiveWithOverrule($siteConfiguration, $routeConfiguration);
            ArrayUtility::mergeRecursiveWithOverrule($siteConfiguration, $routeConfiguration2);

            $event->setConfiguration($siteConfiguration);
        }
    }
}
