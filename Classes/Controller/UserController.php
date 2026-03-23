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

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;



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

        $user->setIsOnline();

        $online = $this->frontendUserRepository->isUserOnline($user->getUid());

        $this->view->assign('user', $user);
        $this->view->assign('feUser', $feUser);
        $this->view->assign('isOnline', $online);

        return $this->htmlResponse();
    }


    public function settingsAction(): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        $user = $this->frontendUserRepository->findByUid($userId);

        $this->view->assign('user', $user);


        return $this->htmlResponse();
    }



    public function updateProfileImageAction(): ResponseInterface
    {
        // 1️⃣ Aktuellen User holen
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

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

            $this->addFlashMessage('Profilbild erfolgreich aktualisiert.');
        } else {
            $this->addFlashMessage('Keine Datei ausgewählt.', '', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING);
        }

        return $this->redirect('settings');
    }
}
