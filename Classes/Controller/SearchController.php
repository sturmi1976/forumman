<?php

declare(strict_types=1);

namespace Lanius\Forumman\Controller;

use Lanius\Forumman\Service\ElasticsearchService;
use Psr\Http\Message\ResponseInterface;
use Lanius\Forumman\Pagination\ElasticsearchPaginator;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Lanius\Forumman\Domain\Repository\PostsRepository;
use Lanius\Forumman\Domain\Repository\ForumsRepository;
use Lanius\Forumman\Domain\Repository\CategoriesRepository;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class SearchController extends ActionController
{

    protected ?CategoriesRepository $categoriesRepository = null;
    protected ?ForumsRepository $forumsRepository = null;
    protected ?PostsRepository $postsRepository = null;

    public function injectCategoriesRepository(CategoriesRepository $categoriesRepository): void
    {
        $this->categoriesRepository = $categoriesRepository;
    }

    public function injectForumsRepository(ForumsRepository $forumsRepository): void
    {
        $this->forumsRepository = $forumsRepository;
    }

    public function injectPostsRepository(PostsRepository $postsRepository): void
    {
        $this->postsRepository = $postsRepository;
    }

    /**
     * @var \Lanius\Forumman\Domain\Repository\FrontendUserRepository|null
     */
    protected ?FrontendUserRepository $frontendUserRepository = null;

    /**
     * @param FrontendUserRepository $frontendUserRepository
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    protected ElasticsearchService $elasticsearchService;

    public function injectElasticsearchService(
        ElasticsearchService $elasticsearchService
    ): void {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Show search form
     */
    public function formAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * Search action
     */
    public function searchAction(): ResponseInterface
    {

        $request = $this->request;

        $query = (string)($request->hasArgument('query') ? $request->getArgument('query') : '');
        $currentPage = (int)($request->hasArgument('currentPage') ? $request->getArgument('currentPage') : 1);

        $itemsPerPage = (int)($this->settings['itemsPerPage'] ?? 10);
        $maximumLinks = 5;

        $items = [];
        $totalItems = 0;

        // -----------------------------
        // Elasticsearch Query (MIT Pagination)
        // -----------------------------
        if ($query !== '') {
            $result = $this->elasticsearchService->search(
                $query,
                $currentPage,
                $itemsPerPage
            );

            $items = [];

            //DebuggerUtility::var_dump($result);

            foreach ($result['hits'] as $hit) {

                //DebuggerUtility::var_dump($hit);

                $type = $hit['type'];

                if ($type === 'post') {

                    $items[] = [
                        'type' => 'post',
                        'uid' => $hit['uid'],
                        'forum' => $hit['forum'],
                        'title' => $hit['title'],
                        'content' => $hit['content'],
                        'username' => $hit['username'],
                        '_score' => $hit['_score'],
                        'label' => $this->getRankingLabel($hit),
                    ];
                }

                if ($type === 'user') {

                    $items[] = [
                        'type' => 'user',
                        'uid' => $hit['uid'],
                        'username' => $hit['username'],
                        'name' => $hit['name'],
                        'email' => $hit['email'],
                        '_score' => $hit['_score'],
                        'postCount' => $hit['postCount'],
                        'usergroup' => $hit['usergroup'],

                    ];
                }
            }

            $totalItems = $result['total'];
        }


        $paginator = new ElasticsearchPaginator(
            $items,
            $totalItems,
            $currentPage,
            $itemsPerPage
        );

        $pagination = new SlidingWindowPagination(
            $paginator,
            $maximumLinks
        );



        $pagination = new SlidingWindowPagination($paginator, $maximumLinks);

        // -----------------------------
        // Assign to Fluid
        // -----------------------------
        $this->view->assignMultiple([
            'query' => $query,
            'results' => $items,
            'total' => $totalItems,

            'paginator' => $paginator,
            'pagination' => $pagination,

            'currentPage' => $currentPage,
            'previousPage' => $pagination->getPreviousPageNumber(),
            'nextPage' => $pagination->getNextPageNumber(),
        ]);

        return $this->htmlResponse();
    }




    private function getRankingLabel(array $hit): string
    {
        //DebuggerUtility::var_dump($hit);
        $score = (float)($hit['_score'] ?? 0);

        $createdAt = (int)($hit['crdate'] ?? time());

        $ageInHours = (time() - $createdAt) / 3600;

        // 🔥 Zeitgewichteter Score
        $hotScore = $score / (1 + ($ageInHours / 24));

        // 🔥 Hot
        if ($hotScore >= 5) {
            return '🔥 Hot';
        }

        // 📈 Trending
        if ($hotScore >= 2) {
            return '📈 Trending';
        }

        // 🆕 New (nur nach Zeit)
        if ($ageInHours < 12) {
            return '🆕 New';
        }

        return '⭐ Relevant';
    }
}
