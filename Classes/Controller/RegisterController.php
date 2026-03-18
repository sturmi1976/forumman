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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;


class RegisterController extends ActionController
{

    protected ?\Lanius\Forumman\Domain\Repository\UserRepository $userRepository = null;

    public const SESSION_KEY = 'forumRegister';

    public function injectUserRepository(
        \Lanius\Forumman\Domain\Repository\UserRepository $userRepository
    ): void {
        $this->userRepository = $userRepository;
    }

    /* =========================================================
     * INDEX
     * ========================================================= */
    public function indexAction(): ResponseInterface
    {
        $feUser = $this->getFrontendUser();

        // POST
        if ($this->request->hasArgument('submit')) {
            return $this->handleSubmit($feUser);
        }

        // GET → Captcha erzeugen
        $this->createCaptcha($feUser);
        return $this->htmlResponse();
    }

    /* =========================================================
     * SUBMIT
     * ========================================================= */
    private function handleSubmit(FrontendUserAuthentication $feUser): ResponseInterface
    {
        $args = $this->request->getArguments();

        $username  = trim((string)($args['username'] ?? ''));
        $email     = trim((string)($args['email'] ?? ''));
        $pass1     = (string)($args['password1'] ?? '');
        $pass2     = (string)($args['password2'] ?? '');
        $captchaIn = (int)($args['captcha'] ?? 0);

        $this->view->assignMultiple([
            'username' => $username,
            'email'    => $email,
        ]);

        // -----------------------------------------------------
        // Captcha prüfen
        // -----------------------------------------------------
        $session = $this->getSession($feUser);

        if (!isset($session['captcha'])) {
            $this->view->assign('error_captcha', 'SESSION ERROR');
            $this->createCaptcha($feUser);
            return $this->htmlResponse();
        }

        if ((int)$session['captcha'] !== $captchaIn) {
            $this->view->assign(
                'error_captcha',
                LocalizationUtility::translate('captcha.wrong', 'forumman')
            );
            $this->createCaptcha($feUser);
            return $this->htmlResponse();
        }

        // Captcha verbrauchen
        unset($session['captcha']);
        $this->storeSession($feUser, $session);

        // -----------------------------------------------------
        // Validierungen
        // -----------------------------------------------------
        if ($pass1 !== $pass2) {
            $this->view->assign(
                'error_passwords',
                LocalizationUtility::translate('password.null', 'forumman')
            );
            $this->createCaptcha($feUser);
            return $this->htmlResponse();
        }

        if (strlen($pass1) < 8) {
            $this->view->assign(
                'error_passwords2',
                LocalizationUtility::translate('password2.null', 'forumman')
            );
            $this->createCaptcha($feUser);
            return $this->htmlResponse();
        }


        if ($this->userRepository->usernameExists($username)) {
            $this->view->assign(
                'error_username',
                //LocalizationUtility::translate('username.exists', 'forumman')
                'Username existiert bereits'
            );

            $this->createCaptcha($feUser);
            return $this->htmlResponse();
        }

        if ($this->userRepository->emailExists($email)) {
            $this->view->assign(
                'error_email',
                'E-Mail Adresse existiert bereits.'
            );
            $this->createCaptcha($feUser);
            return $this->htmlResponse();
        }

        // -----------------------------------------------------
        // User anlegen
        // -----------------------------------------------------
        $pid = (int)$this->settings['userStorageFolder'];
        $md5 = md5($username);

        $uid = $this->userRepository->insertUser([
            'username'  => $username,
            'slug' => $this->generateSlug($username),
            'email'     => $email,
            'password1' => $pass1,
            'pid'       => $pid,
            'md5_hash'  => $md5,
        ]);

        if (!$uid) {
            $this->view->assign('error', 'User konnte nicht angelegt werden.');
            return $this->htmlResponse();
        }

        $this->sendConfirmationMail($username, $email, $md5);

        return $this->redirect('success');
    }

    /* =========================================================
     * CONFIRMATION
     * ========================================================= */
    public function confirmationAction(): ResponseInterface
    {
        $md5 = (string)($this->request->getArgument('md5') ?? '');

        if ($md5 === '') {
            $this->view->assign('error', 'Ungültiger Link.');
            return $this->htmlResponse();
        }

        $user = $this->userRepository->findUserByMd5($md5);

        if (!isset($user['uid'])) {
            $this->view->assign('error', 'Link ungültig oder abgelaufen.');
            return $this->htmlResponse();
        }

        $this->userRepository->activateUser((int)$user['uid']);

        $this->view->assign('success', 'Account erfolgreich aktiviert.');
        return $this->htmlResponse();
    }

    public function successAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /* =========================================================
     * SESSION HELPERS
     * ========================================================= */
    private function getFrontendUser(): FrontendUserAuthentication
    {
        /** @var FrontendUserAuthentication $feUser */
        $feUser = $this->request->getAttribute('frontend.user');

        if (!$feUser instanceof FrontendUserAuthentication) {
            throw new \RuntimeException('FE User nicht verfügbar');
        }

        return $feUser;
    }

    private function getSession(FrontendUserAuthentication $feUser): array
    {
        $data = $feUser->getKey('ses', self::SESSION_KEY);
        return is_array($data) ? $data : [];
    }

    private function storeSession(FrontendUserAuthentication $feUser, array $data): void
    {
        $feUser->setKey('ses', self::SESSION_KEY, $data);
        $feUser->storeSessionData();
    }

    /* =========================================================
     * CAPTCHA
     * ========================================================= */
    private function createCaptcha(FrontendUserAuthentication $feUser): void
    {
        $captcha = $this->generateCaptchaImage();

        $session = $this->getSession($feUser);
        $session['captcha'] = $captcha['result'];
        $this->storeSession($feUser, $session);

        $this->view->assign('captchaImage', $captcha['image']);
    }

    protected function generateCaptchaImage(int $width = 120, int $height = 40): array
    {
        $num1 = random_int(1, 9);
        $num2 = random_int(1, 9);
        $result = $num1 + $num2;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        imagestring($image, 5, 25, 12, "$num1 + $num2 = ?", $black);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return [
            'image'  => 'data:image/png;base64,' . base64_encode($imageData),
            'result' => $result,
        ];
    }

    /* =========================================================
     * MAIL
     * ========================================================= */

    private function sendConfirmationMail(string $username, string $email, string $md5): void
    {
        $confirmationUrl = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setArguments([
                'tx_forumman_forumforumregister' => [
                    'controller' => 'Register',
                    'action'     => 'confirmation',
                    'md5'        => $md5,
                ],
            ])
            ->build();

        $subject = 'Freischaltung im Forum';

        $mailing = new FluidEmail();
        $mailing
            ->to($email)
            ->from(new Address('info@administrator.de', 'Admin Name'))
            ->subject($subject)
            ->format(FluidEmail::FORMAT_HTML)
            ->setTemplate('Register/Confirmation')
            ->assign('username', ucfirst($username))
            ->assign('confirmation_link', $confirmationUrl);
        GeneralUtility::makeInstance(MailerInterface::class)->send($mailing);
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
}
