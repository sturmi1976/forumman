<?php

declare(strict_types=1);

namespace Lanius\Forumman\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;
use Lanius\Forumman\Domain\Repository\GroupRepository;
use TYPO3\CMS\Core\Cache\CacheTag;
use \TYPO3\CMS\Core\Cache\CacheManager;
use Psr\Http\Message\ServerRequestInterface;

class LastLoggedInController extends ActionController
{
    protected FrontendUserRepository $frontendUserRepository;
    protected GroupRepository $groupRepository;

    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }


    public function injectGroupRepository(GroupRepository $groupRepository): void
    {
        $this->groupRepository = $groupRepository;
    }

    /**
     * Show last 3 logged-in users
     */
    public function indexAction(): ResponseInterface
    {

        $lastUsers = $this->frontendUserRepository->findLastLoggedInUsersObjects(3);
        foreach ($lastUsers as $user) {
            $user->getAge();
        }

        // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('lastUsersCacheTag'),
        );

        $this->view->assign('lastUsers', $lastUsers);
        return $this->htmlResponse();
    }
}
