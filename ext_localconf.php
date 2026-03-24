<?php

declare(strict_types=1);

use Lanius\Forumman\Controller\ForumController;
use Lanius\Forumman\Controller\LoginController;
use Lanius\Forumman\Controller\UserController;
use Lanius\Forumman\Controller\RegisterController;
use Lanius\Forumman\Controller\MessageController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Lanius\Forumman\Controller\WhoIsOnlineController;
use Lanius\Forumman\Controller\LastLoggedInController;
use Lanius\Forumman\Controller\NewUserController;

ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumlist',
    [
        ForumController::class => 'index,showThreads,newThread,createThread,show,replyWrite',
        UserController::class  => 'show,settings,updateProfileImage',
    ],
    [
        ForumController::class => 'createThread,replyWrite',
        UserController::class  => 'show,settings,updateProfileImage',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);
ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumlogin',
    [
        LoginController::class => 'index,logout',
    ],
    [
        LoginController::class => 'index,logout',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);
ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumregister',
    [
        RegisterController::class => 'index,success,confirmation',
    ],
    [
        RegisterController::class => 'index,confirmation',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);
ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumlist',
    [
        MessageController::class => 'send,showMailbox,show,delete'
    ],
    [
        MessageController::class => 'send,showMailbox,show,delete'
    ],
);
ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumWhoIsOnline',
    [
        WhoIsOnlineController::class => 'index,ajax'
    ],
    [
        WhoIsOnlineController::class => 'ajax'
    ],
);
ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumLastUsersOnline',
    [
        LastLoggedInController::class => 'index'
    ],
    [
        LastLoggedInController::class => ''
    ],
);
ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumNewUser',
    [
        NewUserController::class => 'index'
    ],
    [
        NewUserController::class => ''
    ],
);


// Mail Templates for Fluid Mail
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][800] = 'EXT:forumman/Resources/Private/Templates/Email';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][800] = 'EXT:forumman/Resources/Private/Layouts/Email';
