<?php

declare(strict_types=1);

namespace Lanius\Forumman\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use Lanius\Forumman\Domain\Model\Message;
use Lanius\Forumman\Domain\Repository\MessageRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use Symfony\Component\Mime\Address;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;


final class MessageController extends ActionController
{
    protected MessageRepository $messageRepository;
    protected PersistenceManagerInterface $persistenceManager;
    protected FrontendUserRepository $frontendUserRepository;

    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    // Setter-Injection
    public function injectMessageRepository(MessageRepository $messageRepository): void
    {
        $this->messageRepository = $messageRepository;
    }

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function sendAction(): ResponseInterface
    {
        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();


        $receiver = $this->request->getArgument('receiver') ?? null;
        $subject  = $this->request->getArgument('subject') ?? '';
        $content  = $this->request->getArgument('content') ?? '';

        /** @var FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');

        $senderUser = $this->frontendUserRepository->findByUid((int)$feUser->user['uid']);
        $receiverUser = $this->frontendUserRepository->findByUid((int)$receiver);



        if (!$receiver) {
            $receiver_error = LocalizationUtility::translate(
                'receiver_error',
                'Forumman',
                [],
                $languageKey
            );
            return new JsonResponse([
                'success' => false,
                'error' => '' . $receiver_error . ''
            ]);
        }

        if (trim($subject) === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Bitte einen Betreff eingeben.'
            ]);
        }

        if (trim($content) === '') {
            $content_error = LocalizationUtility::translate(
                'content_error',
                'Forumman',
                [],
                $languageKey
            );
            return new JsonResponse([
                'success' => false,
                'error' => '' . $content_error . ''
            ]);
        }


        // --- Nachricht speichern ---
        $message = new \Lanius\Forumman\Domain\Model\Message();
        $message->setSender($senderUser);
        $message->setReceiver($receiverUser);
        $message->setSubject($subject);
        $message->setContent($content);
        $message->setSendAt(time());

        $this->messageRepository->add($message);
        $this->persistenceManager->persistAll();

        $linkToForum = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setArguments([
                'tx_forumman_forumforumlist' => [
                    'controller' => 'Forum',
                    'action'     => 'index',
                ],
            ])
            ->build();

        $subject = LocalizationUtility::translate(
            'subject_new_message',
            'Forumman',
            [],
            $languageKey
        );


        $mailing = new FluidEmail();
        $mailing
            ->to($receiverUser->getEmail())
            ->from(new Address('info@administrator.de', 'Admin Name'))
            ->subject($subject)
            ->format(FluidEmail::FORMAT_HTML)
            ->setTemplate('Message/NewMessage')
            ->assignMultiple([
                'username' => ucfirst($receiverUser->getUsername()),
                'link' => $linkToForum,
                'languageKey' => $languageKey,
            ]);
        GeneralUtility::makeInstance(MailerInterface::class)->send($mailing);


        $success = LocalizationUtility::translate(
            'success_message',
            'Forumman',
            [],
            $languageKey
        );

        return new JsonResponse([
            'success' => true,
            'message' => '' . $success . ''
        ]);
    }


    public function showMailboxAction(): ResponseInterface
    {
        $queryParams = $this->request->getQueryParams()['tx_forumman_forumforumlist'];

        /** @var FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');

        if (!isset($feUser->user['uid']) || $queryParams['user'] != $feUser->user['uid']) {
            return $this->redirectToUri(
                $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid((int)$this->settings['forumPagePid'])
                    ->build()
            );
        }

        $messages = $this->messageRepository->findByReceiver((int)$feUser->user['uid']);

        $this->view->assignMultiple([
            'messages' => $messages,
            'user' => $feUser
        ]);

        return $this->htmlResponse();
    }



    public function showAction(\Lanius\Forumman\Domain\Model\Message $message): ResponseInterface
    {
        /** @var FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');

        if (!$message->isRead()) {
            $message->setIsRead(true);

            $this->messageRepository->update($message);
            $this->persistenceManager->persistAll();
        }

        $this->view->assign('message', $message);
        $this->view->assign('date', $message->getSendAt());
        $this->view->assign('feUser', $feUser);
        return $this->htmlResponse();
    }




    public function deleteAction(\Lanius\Forumman\Domain\Model\Message $message)
    {
        /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');
        $currentUserId = (int)($feUser->user['uid'] ?? 0);

        if ($message->getReceiver()->getUid() !== $currentUserId) {
            $this->addFlashMessage('Sie dürfen diese Nachricht nicht löschen.', '');
            return $this->redirect('showMailbox');
        }

        // Nachricht löschen
        $this->messageRepository->remove($message);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage('Die Nachricht wurde erfolgreich gelöscht.', '');

        return $this->redirect('showMailbox', 'Message', 'forumman', ['user' => $currentUserId]);
    }
}
