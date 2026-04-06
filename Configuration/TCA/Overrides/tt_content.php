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
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumLastUsersOnline',
    'Forum: Last user online',
    'apps-pagetree-folder-contains-fe_users',
    'Forum',
    'Anzeigebox wer zuletzt online war',
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumNewUser',
    'Forum: New Users',
    'apps-pagetree-folder-contains-fe_users',
    'Forum',
    'Anzeigebox der 3 neuesten Users',
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumStats',
    'Forum: Statistics',
    'install-clear-cache',
    'Forum',
    'Anzeigebox für Statistiken',
    'FILE:EXT:forumman/Configuration/FlexForms/Statistics.xml'
);
ExtensionUtility::registerPlugin(
    'Forumman',
    'ForumForumSearch',
    'Forum: Elasticsearch Suche',
    'install-clear-cache',
    'Forum-Suche',
    'Suchergebnisse und Formular: Hierfür wird Elasticsearch benötigt - in der Extensionkonfiguration müssen die Einstellungen zu Elasticsearch getätigt werden.',
);
