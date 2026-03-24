<?php

namespace Lanius\Forumman\DataProcessing;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Routing\PageRouter;

/**
 * ExtensionRecordToMenuProcessor
 */
class AddRecordToMenuProcessor implements DataProcessorInterface
{

    /**
     * The content object renderer
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * The processor configuration
     *
     * @var array
     */
    protected $processorConfiguration;

    /**
     * Process
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {

        $this->cObj = $cObj;
        $this->processorConfiguration = $processorConfiguration;

        if (!$this->processorConfiguration['addToMenus']) {
            return $processedData;
        }

        $request = $GLOBALS['TYPO3_REQUEST'];
        $queryParams = $request->getQueryParams();


        if (isset($queryParams['tx_forumman_forumforumlist']['forumUid'])) {
            $recordTable = 'tx_forumman_domain_model_forums';
            $recordUid = (int) $queryParams['tx_forumman_forumforumlist']['forumUid'] ?? null;
        }
        if (isset($queryParams['tx_forumman_forumforumlist']['forum'])) {
            $recordTable = 'tx_forumman_domain_model_forums';
            $recordUid = (int) $queryParams['tx_forumman_forumforumlist']['forum'] ?? null;
        }


        // Configuration for any other models (like above) here...
        if (isset($queryParams['tx_forumman_forumforumlist']['forumUid']) || isset($queryParams['tx_forumman_forumforumlist']['forum'])) {
            $record = $this->getExtensionRecord($recordTable, $recordUid);
            if ($record) {
                $menus = GeneralUtility::trimExplode(',', $this->processorConfiguration['addToMenus'], true);
                foreach ($menus as $menu) {
                    if (isset($processedData[$menu])) {
                        $this->addExtensionRecordToMenu($record, $processedData[$menu]);
                    }
                }
            }
        }

        return $processedData;
    }

    /**
     * Add the extension record to the menu items
     *
     * @param array $record
     * @param array $menu
     */
    protected function addExtensionRecordToMenu(array $record, array &$menu)
    {
        foreach ($menu as &$menuItem) {
            $menuItem['current'] = 0;
        }

        // PID der Seite, auf der das Forum-Plugin liegt
        $targetPageUid = $this->processorConfiguration['forummanUid'];

        $parameters = [
            'tx_forumman_forumforumlist' => [
                'controller' => 'Forum',
                'action' => 'showThreads',
                'forumUid' => $record['uid'],
            ]
        ];

        // URL generated with cObj->typoLink
        $link = $this->cObj->typoLink_URL([
            'parameter' => $targetPageUid,
            'additionalParams' => '&' . http_build_query($parameters),
            'useCacheHash' => 1,
            'returnLast' => 'url'
        ]);


        $menu[] = [
            'data' => $record,
            'title' => $record['title'],
            'active' => 1,
            'current' => 1,
            'link' => $link
        ];
    }

    /**
     * Get the extension record
     *
     * @param string $recordTable
     * @param int $recordUid
     * @return array
     */


    protected function getExtensionRecord(string $recordTable, int $recordUid): array
    {
        if (!$recordTable || !$recordUid) {
            return [];
        }


        /** @var LanguageAspect $languageAspect */
        $languageAspect = GeneralUtility::makeInstance(Context::class)
            ->getAspect('language');


        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($recordTable);

        $row = $connection->select(
            ['*'],
            $recordTable,
            ['uid' => $recordUid]
        )->fetchAssociative();

        if (!$row) {
            return [];
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);


        $overlay = $pageRepository->getLanguageOverlay(
            $recordTable,
            $row,
            $languageAspect
        );

        return $overlay ?? $row;
    }
}
