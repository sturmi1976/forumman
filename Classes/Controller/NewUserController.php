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

class NewUserController extends ActionController
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
     * Show last 3 new users
     */
    public function indexAction(): ResponseInterface
    {

        $newUsers = $this->frontendUserRepository->findNewUsersObjects(3);

        foreach ($newUsers as $user) {
            $user->getAge();
        }

        // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('newUsersCacheTag'),
        );

        $this->view->assign('newUsers', $newUsers);
        return $this->htmlResponse();
    }
}
