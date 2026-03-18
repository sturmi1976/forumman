<?php

declare(strict_types=1);

namespace Lanius\Forumman\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use \TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Core\Utility\StringUtility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\PageTitle\RecordTitleProvider;

use Lanius\Forumman\Domain\Repository\CategoriesRepository;
use Lanius\Forumman\Domain\Repository\ForumsRepository;
use Lanius\Forumman\Domain\Repository\PostsRepository;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;

use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;

final class ForumController extends ActionController
{
    public function __construct(
        private readonly RecordTitleProvider $recordTitleProvider,
    ) {}

    protected ?CategoriesRepository $categoriesRepository = null;
    protected ?ForumsRepository $forumsRepository = null;
    protected ?PostsRepository $postsRepository = null;

    /**
     * @var PersistenceManagerInterface
     */
    protected ?PersistenceManagerInterface $persistenceManager = null;

    /**
     * Property Injection für PersistenceManager
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

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

    /**
     * Übersicht: Alle Kategorien + zugehörige Foren
     */
    public function indexAction(): ResponseInterface
    {
        $categories = $this->categoriesRepository->findAllCategoriesAndForums();
        $this->view->assign('categories', $categories);

        return $this->htmlResponse();
    }

    /**
     * Zeigt Threads eines Forums mit Pagination
     *
     * @param int $forumUid UID des Forums
     */
    public function showThreadsAction(int $forumUid): ResponseInterface
    {

        $forum = $this->forumsRepository->findByUid($forumUid);
        if (!$forum) {
            throw new \RuntimeException('Forum not found', 404);
        }

        // Title Tag
        if ($forum->getTitle()) {
            $this->recordTitleProvider->setTitle($forum->getTitle());
        }

        // Alle Threads (Posts ohne Parent) für dieses Forum
        $allThreads = $this->postsRepository->findThreadsByForum($forumUid);

        // Pagination Parameter
        $currentPage = (int)($this->request->hasArgument('currentPage') ? $this->request->getArgument('currentPage') : 1);
        $itemsPerPage = 10;
        $maximumLinks = 5;

        // Extbase Paginator
        $paginator = new QueryResultPaginator(
            $allThreads,
            $currentPage,
            $itemsPerPage
        );

        // Sliding Window Pagination
        $pagination = new SlidingWindowPagination($paginator, $maximumLinks);

        $paginatedThreads = $paginator->getPaginatedItems();

        // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('forum_' . $forumUid),
        );

        //$cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        //$cacheManager->flushCachesByTag('forum_' . $forumUid);



        $this->view->assignMultiple([
            'forum' => $forum,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'threads' => $paginatedThreads,
            'previousPage' => $pagination->getPreviousPageNumber(),
            'nextPage' => $pagination->getNextPageNumber(),
        ]);

        return $this->htmlResponse();
    }


    public function showAction(int $post): ResponseInterface
    {
        // Post laden
        $postObject = $this->postsRepository->findByUid($post);

        $forum = $this->forumsRepository->findByUid($postObject->getForum());

        if (!$postObject) {
            throw new \RuntimeException('Post not found', 404);
        }

        // Title Tag
        if ($postObject->getTitle()) {
            $this->recordTitleProvider->setTitle($postObject->getTitle());
        }

        // --- Pagination für Replies ---
        $allReplies = $this->postsRepository->findRepliesByParent($post);

        $currentPage = (int)($this->request->hasArgument('currentPage') ? $this->request->getArgument('currentPage') : 1);
        $itemsPerPage = 1;
        $maximumLinks = 5;

        $paginator = new QueryResultPaginator($allReplies, $currentPage, $itemsPerPage);
        $pagination = new SlidingWindowPagination($paginator, $maximumLinks);
        $paginatedReplies = $paginator->getPaginatedItems();

        // --- Template Assign ---
        $this->view->assignMultiple([
            'post' => $postObject,
            'forum' => $forum,
            'replies' => $paginatedReplies,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'previousPage' => $pagination->getPreviousPageNumber(),
            'nextPage' => $pagination->getNextPageNumber(),
        ]);

        return $this->htmlResponse();
    }




    public function newThreadAction(int $forumUid): ResponseInterface
    {
        $forum = $this->forumsRepository->findByUid($forumUid);

        $this->view->assign('forum', $forum);
        return $this->htmlResponse();
    }





    public function createThreadAction(\Lanius\Forumman\Domain\Model\Posts $post): ResponseInterface
    {

        $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesByTag('user_' . $userId);

        $userIsLogin = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        if (!isset($userIsLogin) || $userIsLogin == FALSE) {
            return $this->redirect(
                'showThreads',
                'Forum',
                'Forumman',
                ['forumUid' => $post->getForum()]
            );
        }

        if ($userId) {
            $user = $this->frontendUserRepository->findByUid($userId);
            $post->setUser($user);
        }

        $post->setCreatedAt(time());

        $post->setSlug($this->generateUniqueSlug($post->getTitle()));

        $this->postsRepository->add($post);

        // Forum holen
        $forum = $post->getForum();

        // Thread erhöhen
        $forum->setThreadCount($forum->getThreadCount() + 1);

        // Post erhöhen
        $forum->setPostCount($forum->getPostCount() + 1);

        $this->forumsRepository->update($forum);

        $this->persistenceManager->persistAll();

        // Redirect auf Einzelansicht des neuen Posts
        return $this->redirect(
            'show',
            'Forum',
            'Forumman',
            ['post' => $post->getUid(), 'forum' => $post->getForum()]
        );

        return $this->htmlResponse();
    }



    public function generateSlug(string $string): string
    {
        // UTF-8 → ASCII (Umlaute etc.)
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        // Kleinbuchstaben
        $string = strtolower($string);

        // Alles, was nicht Buchstaben, Zahlen oder Leerzeichen ist, entfernen
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);

        // Mehrere Leerzeichen oder Bindestriche zusammenfassen
        $string = preg_replace('/[\s-]+/', ' ', $string);

        // Leerzeichen durch Bindestriche ersetzen
        $string = preg_replace('/\s/', '-', $string);

        // Am Anfang/Ende keine Bindestriche
        $string = trim($string, '-');

        return $string;
    }

    protected function generateUniqueSlug(string $title): string
    {
        $baseSlug = $this->generateSlug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->postsRepository->findOneBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }


    private function getFrontendUserId(): int
    {
        /** @var FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');

        return (int)($feUser->user['uid'] ?? 0);
    }
}
