<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumlist',
    'Forum: Forumlist',
    'ext-forumman-plugin',
    'Forum',
    'Zur Darstellung der Foren',
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumlogin',
    'Forum: Login form',
    'ext-forumman-plugin',
    'Forum',
    'Login Formular',
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumregister',
    'Forum: Register form',
    'ext-forumman-plugin',
    'Forum',
    'Registrierungs Formular',
);
