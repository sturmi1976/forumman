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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Lanius\Forumman\Domain\Model\FrontendUser;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\FileRepository;

use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;



final class UserController extends ActionController
{

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


        if ($user->getBirthday()) {
            $birthday = \DateTime::createFromFormat('d-m-Y', $user->getBirthday());
            if ($birthday) {
                $today = new \DateTime();
                $age = $today->diff($birthday)->y;
                $user->setAge($age);
            }
        }

        if (!empty($user->getBirthday())) {
            $birthDate = new \DateTime($user->getBirthday());
            $today = new \DateTime('today');
            $age2 = $birthDate->diff($today)->y;
            $user->_setProperty('age2', $age2);
        }

        $user->getUserGroup();

        $user->setIsOnline();

        $online = $this->frontendUserRepository->isUserOnline($user->getUid());


        $this->view->assign('user', $user);
        $this->view->assign('feUser', $feUser);
        $this->view->assign('isOnline', $online);

        return $this->htmlResponse();
    }


    public function settingsAction(): ResponseInterface
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\RequestInterface $request */
        $request = $this->request;
        // GET-Parameter für *dieses Plugin / Controller*
        $arguments = $request->getArguments();

        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        $user = $this->frontendUserRepository->findByUid($userId);

        $this->view->assign('user', $user);
        $this->view->assign('arguments', $arguments);


        return $this->htmlResponse();
    }



    public function saveSettingsAction(\Lanius\Forumman\Domain\Model\FrontendUser $user): ResponseInterface
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\RequestInterface $request */
        $request = $this->request;
        // GET-Parameter für *dieses Plugin / Controller*
        $arguments = $request->getArguments();

        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();

        $linkErrorText = LocalizationUtility::translate(
            'settings.linkError',
            'Forumman',
            [],
            $languageKey
        );

        $facebooklink = $arguments['user']['facebooklink'] ?? '';
        if ($facebooklink !== '' && !$this->isValidUrl($facebooklink)) {
            $this->addFlashMessage(
                $linkErrorText,
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('settings');
        }

        $twitterlink = $arguments['user']['twitterlink'] ?? '';
        if ($twitterlink !== '' && !$this->isValidUrl($twitterlink)) {
            $this->addFlashMessage(
                $linkErrorText,
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('settings');
        }

        $linkedinlink = $arguments['user']['linkedinlink'] ?? '';
        if ($linkedinlink !== '' && !$this->isValidUrl($linkedinlink)) {
            $this->addFlashMessage(
                $linkErrorText,
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('settings');
        }

        $instagramlink = $arguments['user']['instagramlink'] ?? '';
        if ($instagramlink !== '' && !$this->isValidUrl($instagramlink)) {
            $this->addFlashMessage(
                $linkErrorText,
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('settings');
        }

        $youtubelink = $arguments['user']['youtubelink'] ?? '';
        if ($youtubelink !== '' && !$this->isValidUrl($youtubelink)) {
            $this->addFlashMessage(
                $linkErrorText,
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('settings');
        }

        $xinglink = $arguments['user']['xinglink'] ?? '';
        if ($xinglink !== '' && !$this->isValidUrl($xinglink)) {
            $this->addFlashMessage(
                $linkErrorText,
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('settings');
        }


        $this->frontendUserRepository->update($user);

        $this->addFlashMessage(
            'Einstellungen gespeichert',
            '',
            \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK
        );

        return $this->redirect('settings');
    }



    public function updateProfileImageAction(): ResponseInterface
    {
        // 1️⃣ Aktuellen User holen
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        // Language
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        $languageKey = $locale->getLanguageCode();

        if (!$userId) {
            $this->addFlashMessage('Kein eingeloggter User gefunden.', '', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);
            return $this->redirect('settings');
        }

        $user = $this->frontendUserRepository->findByUid($userId);
        if (!$user) {
            $this->addFlashMessage('User nicht gefunden.', '', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);
            return $this->redirect('settings');
        }

        // 2️⃣ Upload holen
        $uploadedFiles = $this->request->getUploadedFiles();

        if (!empty($uploadedFiles['imageUpload']['image'])) {

            $uploadedFile = $uploadedFiles['imageUpload']['image'];

            // temporärer Pfad
            $tmpFilePath = $uploadedFile->getStream()->getMetadata('uri');

            $storageUid = (int)($this->settings['storageUid'] ?? 1);

            /** @var StorageRepository $storageRepository */
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $storage = $storageRepository->findByUid($storageUid);

            // 3️⃣ User-Ordner
            $folderPath = 'user_uploads/' . $userId;

            if ($storage->hasFolder($folderPath)) {
                $falFolder = $storage->getFolder($folderPath);
            } else {
                $falFolder = $storage->createFolder($folderPath, $storage->getRootLevelFolder());
            }

            // 🔥 Optional: alte Bilder löschen (empfohlen)
            foreach ($falFolder->getFiles() as $existingFile) {
                $existingFile->delete();
            }

            // 4️⃣ Datei speichern
            $fileObject = $falFolder->addFile(
                $tmpFilePath,
                $uploadedFile->getClientFilename(),
                DuplicationBehavior::REPLACE
            );


            // Prüfen, ob Upload erfolgreich war
            if (!$fileObject instanceof \TYPO3\CMS\Core\Resource\File) {
                $this->addFlashMessage(
                    'Das Profilbild konnte nicht hochgeladen werden. Bitte überprüfen Sie die Datei.',
                    'Fehler',
                    \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
                );
                return $this->redirect('settings');
            }

            /** @var ResourceFactory $resourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

            // 5️⃣ Core FileReference erstellen (WICHTIG!)
            $coreFileReference = $resourceFactory->createFileReferenceObject([
                'uid_local' => $fileObject->getUid(),
                'uid_foreign' => $user->getUid(),
                'tablenames' => 'fe_users',
                'fieldname' => 'image',
                'pid' => $user->getPid(),
            ]);

            // 6️⃣ Extbase FileReference
            $extbaseFileReference = new ExtbaseFileReference();
            $extbaseFileReference->setOriginalResource($coreFileReference);

            // 7️⃣ User setzen
            $user->setImage($extbaseFileReference);
            $this->frontendUserRepository->update($user);

            $successText = LocalizationUtility::translate(
                'settings.uploadSuccessText',
                'Forumman',
                [],
                $languageKey
            );

            $this->addFlashMessage($successText);
        } else {
            $successText2 = LocalizationUtility::translate(
                'settings.uploadNotSuccessText',
                'Forumman',
                [],
                $languageKey
            );
            $this->addFlashMessage($successText2, '', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING);
        }

        return $this->redirect('settings');
    }



    public function userlistAction(): ResponseInterface
    {
        $findAllUser = $this->frontendUserRepository->findAllUsersObjects();

        // Pagination Parameter
        $currentPage = (int)($this->request->hasArgument('currentPage') ? $this->request->getArgument('currentPage') : 1);
        $itemsPerPage = (int)($this->settings['itemsPerPage'] ?? 10);
        $maximumLinks = 5;

        // Extbase Paginator
        $paginator = new ArrayPaginator(
            $findAllUser,
            $currentPage,
            $itemsPerPage
        );

        // Sliding Window Pagination
        $pagination = new SlidingWindowPagination($paginator, $maximumLinks);

        $paginatedUsers = $paginator->getPaginatedItems();


        // --- Cache-Tags set ---
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $collector = $request->getAttribute('frontend.cache.collector');

        $collector->addCacheTags(
            new CacheTag('userlist'),
        );



        $this->view->assignMultiple([
            'paginator' => $paginator,
            'pagination' => $pagination,
            'users' => $paginatedUsers,
            'previousPage' => $pagination->getPreviousPageNumber(),
            'nextPage' => $pagination->getNextPageNumber(),
            'currentPage' => $currentPage,
        ]);

        return $this->htmlResponse();
    }



    function isValidUrl(string $url): bool
    {
        // Muss mit https:// anfangen
        if (!str_starts_with($url, 'https://')) {
            return false;
        }

        // PHP URL-Validierung
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Prüfen ob Domain eine gültige TLD hat (.de, .com, etc.)
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        // Regex für TLD (z.B. .de, .com, .org, .net, ...)
        if (!preg_match('/\.[a-z]{2,}$/i', $host)) {
            return false;
        }

        return true;
    }
}
