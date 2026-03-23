<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-forumman-plugin' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:forumman/Resources/Public/Icons/Plugin.svg',
    ],
    'ext-forumman-forumlist' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:forumman/Resources/Public/Icons/Forumlist.svg',
    ],
    'ext-forumman-forumlogin' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:forumman/Resources/Public/Icons/Loginbox.svg',
    ],
];
