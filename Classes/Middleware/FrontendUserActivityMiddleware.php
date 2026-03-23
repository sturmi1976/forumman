<?php

namespace Lanius\Forumman\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FrontendUserActivityMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Aktuellen User holen
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = $context->getPropertyFromAspect('frontend.user', 'id');

        if ($userId) {
            // DB Update nur, wenn User aktiv ist
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('fe_users');

            $connection->update(
                'fe_users',
                [
                    'tx_forumman_last_activity' => time(),
                ],
                [
                    'uid' => $userId,
                ]
            );
        }

        // Request weiterreichen
        return $handler->handle($request);
    }
}
