<?php

declare(strict_types=1);

namespace Lanius\Forumman\Controller;

use Lanius\Forumman\Domain\Model\FrontendUser;
use Lanius\Forumman\Domain\Model\Posts;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use \TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Context\Context;

use Lanius\Forumman\Domain\Repository\CategoriesRepository;
use Lanius\Forumman\Domain\Repository\ForumsRepository;
use Lanius\Forumman\Domain\Repository\PostsRepository;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;

use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use Lanius\Forumman\Service\ElasticsearchService;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class ForumController extends ActionController
{

    protected ?CategoriesRepository $categoriesRepository = null;
    protected ?ForumsRepository $forumsRepository = null;
    protected ?PostsRepository $postsRepository = null;

    protected ElasticsearchService $elasticsearchService;

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
     * Forum overview
     */
    public function indexAction(): ResponseInterface
    {
        $categories = $this->categoriesRepository->findAllCategoriesAndForums();

        foreach ($categories as $category) {
            foreach ($category->getForums() as $forum) {

                $threadCount = $this->postsRepository->countThreadsByForum($forum->getUid());
                $postCount   = $this->postsRepository->countPostsByForum($forum->getUid());
                $latestActivity = $this->postsRepository->findLatestActivityByForum($forum->getUid());



                $forum->setLatestActivity($latestActivity);
                //$forum->setLatestPost($latestPost);
                $forum->_setProperty('threadCountDynamic', $threadCount);
                $forum->_setProperty('postCountDynamic', $postCount);
                //$forum->_setProperty('latestPost', $latestPost);
            }
        }


        if (!empty($this->settings['ogImage'])) {

            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

            $contentUid = (int)$this->request->getAttribute('currentContentObject')->data['uid'];

            $files = $fileRepository->findByRelation(
                'tt_content',
                'og_image',
                $contentUid
            );

            if (!empty($files)) {
                $fileReference = reset($files);
                $this->view->assign('ogImageObject', $fileReference);
            }
        }


        /** @var \TYPO3\CMS\Extbase\Mvc\RequestInterface $request */
        $request = $this->request;

         // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('forum_overview'),
        );


        $this->view->assignMultiple([
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Zeigt Threads eines Forums mit Pagination
     *
     * @param int $forumUid UID des Forums
     */
    public function showThreadsAction(int $forumUid): ResponseInterface
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\RequestInterface $request */
        $request = $this->request;

        $language = $this->request->getAttribute('language');
        $languageId = $language->getLanguageId();


        // GET-Parameter für *dieses Plugin / Controller*
        $arguments = $request->getArguments();

        $forum = $this->forumsRepository->findByUid($forumUid);
        if (!$forum) {
            throw new \RuntimeException('Forum not found', 404);
        }


        // Alle Threads (Posts ohne Parent) für dieses Forum
        $allThreads = $this->postsRepository->findThreadsByForum($forumUid, $languageId);

        // Pagination Parameter
        $currentPage = (int)($this->request->hasArgument('currentPage') ? $this->request->getArgument('currentPage') : 1);
        $itemsPerPage = (int)($this->settings['itemsPerPage'] ?? 10);
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

        foreach ($paginatedThreads as $thread) {
            $replyCount = $this->postsRepository->countRepliesByThread($thread->getUid());

            $thread->setReplyCount($replyCount);
        }

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
            'currentPage' => $currentPage,
        ]);

        return $this->htmlResponse();
    }


    public function showAction(int $post): ResponseInterface
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\RequestInterface $request */
        $request = $this->request;
        // GET-Parameter für *dieses Plugin / Controller*
        $arguments = $request->getArguments();

        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();

        $language = $this->request->getAttribute('language');
        $languageId = $language->getLanguageId();

        // Post laden
        $postObject = $this->postsRepository->findByUid($post);

        $forum = $this->forumsRepository->findByUid($postObject->getForum());

        if (!$postObject) {
            throw new \RuntimeException('Post not found', 404);
        }

        if ($postObject->getUser()) {
            $online = $this->frontendUserRepository->isUserOnline($postObject->getUser()->getUid());
        }

        // Online Status für den Post-Autor
        $postUserId = $postObject->getUser()->getUid();
        $isOnline = $this->frontendUserRepository->isUserOnline($postUserId);

        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($languageId);
        // --- Pagination für Replies ---
        $allReplies = $this->postsRepository->findRepliesByParent($post, $languageId);

        $currentPage = (int)($this->request->hasArgument('currentPage') ? $this->request->getArgument('currentPage') : 1);
        $itemsPerPage = (int)($this->settings['itemsPerPage2'] ?? 10);
        $maximumLinks = 5;

        $paginator = new QueryResultPaginator($allReplies, $currentPage, $itemsPerPage);
        $pagination = new SlidingWindowPagination($paginator, $maximumLinks);
        $paginatedReplies = $paginator->getPaginatedItems();

        // Online-Status für jede Reply vorbereiten
        $repliesWithStatus = [];

        foreach ($paginatedReplies as $reply) {
            $user = $reply->getUser();

            // Prüfen, ob User existiert
            if ($user instanceof FrontendUser) {
                $userId = $user->getUid();
                $isOnline2 = $userId ? $this->frontendUserRepository->isUserOnline($userId) : false;
            } else {
                $userId = null;
                $isOnline2 = false;
            }

            $repliesWithStatus[] = [
                'reply' => $reply,
                'isOnline2' => $isOnline2
            ];
        }

        // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('post_' . $postObject->getUid()), // global
            new CacheTag('post_' . $postObject->getUid() . '_page' . $currentPage),
        );




        $url = (string)$this->request->getUri();
        $url = strtok($url, '?');


        // --- Template Assign ---
        $this->view->assignMultiple([
            'post' => $postObject,
            'currentUrl' => $url,
            'isOnline' => $isOnline,
            'forum' => $forum,
            //'replies' => $paginatedReplies,
            'replies' => $repliesWithStatus,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'previousPage' => $pagination->getPreviousPageNumber(),
            'nextPage' => $pagination->getNextPageNumber(),
            'currentPage' => $currentPage,
            'arguments' => $arguments,
            'frontendUser' => $this->getFrontendUserId(),
        ]);

        return $this->htmlResponse();
    }


    public function editAction(int $post): ResponseInterface
    {
        $postObject = $this->postsRepository->findByUid($post);

        if (!$postObject) {
            throw new \RuntimeException('Post not found', 404);
        }

        $currentUser = $this->getFrontendUserId();

        // 🔒 Security Check
        if ($postObject->getUser()->getUid() !== $currentUser) {
            throw new \RuntimeException('Access denied', 403);
        }

        $this->view->assign('post', $postObject);

        return $this->htmlResponse();
    }



    public function updateAction(\Lanius\Forumman\Domain\Model\Posts $post): ResponseInterface
    {
        $currentUser = $this->getCurrentUser();

        // 🔒 Security Check
        if ($post->getUser()->getUid() !== $currentUser->getUid()) {
            throw new \RuntimeException('Access denied', 403);
        }

        $this->postsRepository->update($post);

        $this->addFlashMessage('Post updated successfully');

        return $this->redirect('show', null, null, ['post' => $post->getUid()]);
    }


    public function getCurrentUser(): ?\Lanius\Forumman\Domain\Model\FrontendUser
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userAspect = $context->getAspect('frontend.user');

        $userId = (int)$userAspect->get('id');

        if ($userId === 0) {
            return null;
        }

        return $this->findByUid($userId);
    }


    public function newThreadAction(int $forumUid): ResponseInterface
    {
        $forum = $this->forumsRepository->findByUid($forumUid);

        $this->view->assign('forum', $forum);
        return $this->htmlResponse();
    }



    public function replyWriteAction(): ResponseInterface
    {

        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();

        $language = $this->request->getAttribute('language');
        $languageId = $language->getLanguageId();


        $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            return $this->redirect('showThreads', 'Forum', 'Forumman', [
                'forumUid' => $this->request->getArgument('forum')
            ]);
        }

        if ($this->request->hasArgument('submit')) {

            //$title   = trim($this->request->getArgument('title') ?? '');
            $content = trim($this->request->getArgument('content') ?? '');
            $parentUid = (int)$this->request->getArgument('parent');
            $forumUid  = (int)$this->request->getArgument('forum');


            if ($content === '') {
                $this->view->assign('error', 'Bitte geben Sie einen Inhalt ein.');
                return $this->htmlResponse();
            }

            $reply = new \Lanius\Forumman\Domain\Model\Posts();
            //$reply->setTitle($title);
            $reply->setContent($content);
            $reply->setCreatedAt(time());
            $reply->setParent($parentUid);
            // Forum-Object
            $forumObject = $this->forumsRepository->findByUid($forumUid);


            $reply->setForum($forumObject);

            if ($userId) {
                $user = $this->frontendUserRepository->findByUid($userId);
                $reply->setUser($user);
            }

            $this->postsRepository->add($reply);
            $this->persistenceManager->persistAll();

            $this->postsRepository->setLanguageUidForPost($reply->getUid(), $languageId);

            // Post count ++
            $forumObject->setPostCount($forumObject->getPostCount() + 1);
            $this->forumsRepository->update($forumObject);

            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->flushCachesByTag('user_' . $userId);
            $cacheManager->flushCachesByTag('post_' . $parentUid);

            // Anzahl aller Replies (inkl. neuer)
            $totalReplies = $this->postsRepository->countRepliesByParent($parentUid, $languageId);

            // Items pro Seite (gleich wie in showAction!)
            $itemsPerPage = (int)($this->settings['itemsPerPage2'] ?? 10);

            // Zielseite berechnen
            $targetPage = (int)ceil($totalReplies / $itemsPerPage);


            //$uri = $this->uriBuilder->uriFor('show', ['action' => 'show', 'controller' => 'Forum', 'forum' => $forumObject, 'post' => $parentUid, 'reply' => 1]);

            $uri = $this->uriBuilder
                ->reset()
                ->uriFor(
                    'show',
                    [
                        'forum' => $forumObject,
                        'post' => $parentUid,
                        'currentPage' => $targetPage,
                        'reply' => 1
                    ],
                    'Forum',
                    'Forumman'
                );
            return $this->redirectToUri($uri . '#latest');
        }

        // Initiales Formular rendern
        $postUid = (int)$this->request->getArgument('post');
        $forumUid = (int)$this->request->getArgument('forum');

        $forumObject = $this->forumsRepository->findByUid($forumUid);

        $post = $this->postsRepository->findByUid($postUid);
        $forum = $this->forumsRepository->findByUid((int)$this->request->getArgument('forum'));
        //$forum = $this->forumsRepository->findByUidAndLanguage($forumUid, $languageId);


        $this->view->assignMultiple([
            'forum' => $forum,
            'post' => $post,
            'userid' => $userId
        ]);

        return $this->htmlResponse();
    }



    public function createThreadAction(\Lanius\Forumman\Domain\Model\Posts $post): ResponseInterface
    {
        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();


        $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        $language = $this->request->getAttribute('language');
        $languageId = $language->getLanguageId();


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

        //$post->setSysLanguageUid((int)$languageId);

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

        $this->postsRepository->setLanguageUidForPost($post->getUid(), $languageId);


        $flashContent = LocalizationUtility::translate(
            'flashContentThread',
            'Forumman',
            [],
            $languageKey
        );
        $flashHeadline = LocalizationUtility::translate(
            'flashContentHeadline',
            'Forumman',
            [],
            $languageKey
        );
        $this->addFlashMessage(
            $flashContent,
            $flashHeadline,
            \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK
        );

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
