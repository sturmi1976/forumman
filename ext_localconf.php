<?php

declare(strict_types=1);

use Lanius\Forumman\Controller\ForumController;
use Lanius\Forumman\Controller\LoginController;
use Lanius\Forumman\Controller\UserController;
use Lanius\Forumman\Controller\RegisterController;
use Lanius\Forumman\Controller\MessageController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'Forumman',
    'ForumForumlist',
    [
        ForumController::class => 'index,showThreads,newThread,createThread,show,replyWrite',
        UserController::class  => 'show',
    ],
    [
        ForumController::class => 'createThread,replyWrite',
        UserController::class  => 'show',
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



// Mail Templates for Fluid Mail
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][700] = 'EXT:forumman/Resources/Private/Templates/Email';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][700] = 'EXT:forumman/Resources/Private/Layouts/Email';
