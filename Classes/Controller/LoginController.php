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


class LoginController extends ActionController
{
    /**
     * @var UserRepository
     */
    protected UserRepository $userRepository;

    /**
     * Setter-Injection für Extbase
     */
    public function injectUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
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


    public function indexAction(): ResponseInterface
    {
        $data = $this->request->getArguments();


        if (!empty($data['submit'])) {
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            // User aus DB holen
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('fe_users');

            $user = $connection->createQueryBuilder()
                ->select('*')
                ->from('fe_users')
                ->where('username = :username')
                ->setParameter('username', $username)
                ->executeQuery()
                ->fetchAssociative();

            if (!$user) {
                $this->view->assign('error', 'Benutzer existiert nicht.');
                return $this->htmlResponse();
            }

            if (!password_verify($password, $user['password'])) {
                $this->view->assign('error', 'Passwort falsch.');
                return $this->htmlResponse();
            }


            /** @var FrontendUserAuthentication $feUser */
            $feUser = $this->request->getAttribute('frontend.user');

            if (!$feUser) {
                throw new \RuntimeException('FE-User authentication object not found in request');
            }

            // Session erzeugen
            $session = $feUser->createUserSession($user);
            $feUser->storeSessionData();

            // Cookie setzen
            setcookie('fe_typo_user', $session->getJwt(), 0, '/');

            if (!empty($session)) {
                $referer = $this->request->getServerParams()['HTTP_REFERER'] ?? null;

                if ($referer) {
                    return $this->redirectToUri($referer);
                }
            }


            // Eingeloggten User abfragen
            $context = GeneralUtility::makeInstance(Context::class);
            $isLoggedIn = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
            $userId = $context->getPropertyFromAspect('frontend.user', 'id');
            $name = $context->getPropertyFromAspect('frontend.user', 'username');

            if (isset($userId)) {
                $user_id = (int)$context->getPropertyFromAspect('frontend.user', 'id');
                $this->userRepository->updateOnlineTimestamp($userId);
            }


            if ($isLoggedIn) {
                //$this->view->assign('messages_count', $this->messagesRepository->countByRecipientForNewMessage($userId));
                $this->view->assign('username', ucfirst($name));
                $this->view->assign('userid', $userId);
            }

            $this->view->assign('registerpid', $this->settings['registerpid']);
            $this->view->assign('success', 'Login erfolgreich!');
        }


        $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        $username = $context->getPropertyFromAspect('frontend.user', 'username');

        $this->view->assign('username', $username);


        $this->view->assign('user', $this->frontendUserRepository->findByUid($context->getPropertyFromAspect('frontend.user', 'id')));
        return $this->htmlResponse();
    }



    public function logoutAction(): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');
        $this->userRepository->setUserOffline((int)$userId);
    // Request holen
        /** @var ServerRequestInterface $request */
        $request = $this->request;

    // FE Authentication laden
        /** @var FrontendUserAuthentication $frontendUser */
        $frontendUser = $request->getAttribute('frontend.user');

        if ($frontendUser instanceof FrontendUserAuthentication) {
            // 1) TYPO3-Sitzung beenden
            $frontendUser->logoff();

            // 2) Session aus dem Storage löschen
            $frontendUser->removeSessionData();

            // 3) Cookie löschen
            setcookie(
                'fe_typo_user',
                '',
                time() - 3600,
                '/'
            );
        }

        // Optional: Feedback oder Redirect
        $this->addFlashMessage('Du wurdest ausgeloggt.');

        // Zurück zur aktuellen Seite redirecten
        $referer = $this->request->getServerParams()['HTTP_REFERER'] ?? null;

        if ($referer) {
            return $this->redirectToUri($referer);
        } else {
            return $this->redirectToUri($request->getUri()->getPath());
        }
    }
}
