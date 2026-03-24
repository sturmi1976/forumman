<?php

declare(strict_types=1);

/*
 * This file is part of the package lanius/forumman.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lanius\Forumman\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Lanius\Forumman\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;


class WhoIsOnlineController extends ActionController
{
    protected FrontendUserRepository $frontendUserRepository;

    public function __construct(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }



    public function indexAction(): ResponseInterface
    {
        $onlineUsers = $this->frontendUserRepository->findOnlineUsers(10);

        $this->view->assign('onlineUsers', $onlineUsers);

        // Plain HTML-Output for Ajax
        $content = $this->view->render();

        return $this->htmlResponse($content);
    }


    public function ajaxAction(): ResponseInterface
    {
        $onlineUsers = $this->frontendUserRepository->findOnlineUsers(10);

        $this->view->assign('onlineUsers', $onlineUsers);

        return $this->htmlResponse(); // 
    }
}
