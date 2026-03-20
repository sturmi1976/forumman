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
use TYPO3\CMS\Core\Cache\CacheTag;
use \TYPO3\CMS\Core\Cache\CacheManager;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Lanius\Forumman\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
//use TYPO3\CMS\Core\PageTitle\RecordTitleProvider;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Lanius\Forumman\Domain\Model\FrontendUser;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;


final class UserController extends ActionController
{
    /*
    public function __construct(
        private readonly RecordTitleProvider $recordTitleProvider,
    ) {}
    */

    protected FrontendUserRepository $frontendUserRepository;

    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }



    public function showAction(FrontendUser $user): ResponseInterface
    {
        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();

        $username = ucfirst($user->getUsername());
        $usergroup = $user->getUserGroup();
        $company = $user->getCompany();
        $postCount = $user->getPostCount();

        // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('user_' . $user->getUid()),
        );


        if ($company) {
            $titletag = LocalizationUtility::translate(
                'user.titletag_with_company',
                'Forumman',
                [ucfirst($user->getUsername()), $user->getCompany()],
                $languageKey
            );
        } else {
            $titletag = LocalizationUtility::translate(
                'user.titletag',
                'Forumman',
                [ucfirst($user->getUsername()), $user->getCompany()],
                $languageKey
            );
        }

        /** @var FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');

        //$this->recordTitleProvider->setTitle($titletag);

        if ($user->getBirthday()) {
            $birthday = \DateTime::createFromFormat('d-m-Y', $user->getBirthday());
            if ($birthday) {
                $today = new \DateTime();
                $age = $today->diff($birthday)->y;
                $user->setAge($age);
            }
        }

        $user->setIsOnline();

        $online = $this->frontendUserRepository->isUserOnline($user->getUid());

        $this->view->assign('user', $user);
        $this->view->assign('feUser', $feUser);
        $this->view->assign('isOnline', $online);

        return $this->htmlResponse();
    }
}
