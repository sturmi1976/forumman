<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumlist',
    'Forum: Forumlist',
    'apps-pagetree-page-backend-users-root',
    'Forum',
    'Zur Darstellung der Foren',
    'FILE:EXT:forumman/Configuration/FlexForms/Forum.xml'
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumlogin',
    'Login Box / Settings',
    'mimetypes-x-content-login',
    'Forum',
    'Login Formular und Logout Box inkl. Einstellungsmöglichkeiten für den User.',
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumregister',
    'Forum: Register form',
    'content-webhook',
    'Forum',
    'Registrierungs Formular',
    'FILE:EXT:forumman/Configuration/FlexForms/Register.xml'
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumWhoIsOnline',
    'Forum: Who is online?',
    'apps-pagetree-folder-contains-fe_users',
    'Forum',
    'Anzeigebox wer gerade online ist',
);
