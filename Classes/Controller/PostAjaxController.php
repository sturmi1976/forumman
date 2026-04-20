<?php

namespace Lanius\Forumman\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use Lanius\Forumman\Domain\Model\Posts;
use Lanius\Forumman\Domain\Repository\PostsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

use TYPO3\CMS\Core\Cache\CacheTag;
use \TYPO3\CMS\Core\Cache\CacheManager;

final class PostAjaxController extends ActionController
{
    protected PostsRepository $postsRepository;
    protected PersistenceManagerInterface $persistenceManager;
    protected FrontendUserRepository $frontendUserRepository;


    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    public function injectPostsRepository(PostsRepository $postsRepository): void
    {
        $this->postsRepository = $postsRepository;
    }

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }


    public function editAction(): ResponseInterface
    {
        $raw = $this->request->getBody()->getContents();
        $data = json_decode($raw ?? '', true) ?? [];

        $postId = (int)($data['postId'] ?? 0);
        $content = (string)($data['content'] ?? '');

        // -----------------------------
        // 1. Valid input check
        // -----------------------------
        if ($postId <= 0 || trim($content) === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid input'
            ], 400);
        }

        // -----------------------------
        // 2. Post existiert?
        // -----------------------------
        $post = $this->postsRepository->findByUid($postId);

        if (!$post) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // -----------------------------
        // 3. User eingeloggt?
        // -----------------------------
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = (int)$context->getAspect('frontend.user')->get('id');

        if ($userId <= 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not logged in'
            ], 401);
        }


        if (strlen($content) < 3) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Content too short'
            ], 400);
        }

        // -----------------------------
        // 4. Ownership Check
        // -----------------------------
        $postUser = $post->getUser()?->getUid();

        if ((int)$postUser !== $userId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No permission to edit this post'
            ], 403);
        }

        // -----------------------------
        // 5. Update
        // -----------------------------
        $ok = $this->postsRepository->updateEdit($postId, $content);

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesByTag('post_' . $post->getParent());
        if ($post->getParent() == 0) {
            $cacheManager->flushCachesByTag('post_' . $postId);
        }

        return new JsonResponse([
            'success' => $ok,
            'message' => $ok ? 'Post updated' : 'Update failed'
        ]);
    }
}
