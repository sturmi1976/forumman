<?php

namespace Lanius\Forumman\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use Lanius\Forumman\Service\ElasticsearchService;

class ElasticsearchController
{
    public function __construct(
        private readonly ElasticsearchService $elastic
    ) {}

    public function reindexAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $offset = (int)($data['offset'] ?? 0);

        // Beispiel: Dummy Batch (du kannst hier echte DB holen)
        $posts = $this->getPosts($offset, 50);

        $count = 0;

        foreach ($posts as $post) {
            $this->elastic->index((string)$post['uid'], $post);
            $count++;
        }

        return new JsonResponse([
            'indexed' => $count,
            'nextOffset' => $offset + $count,
            'finished' => $count < 50
        ]);
    }

    private function getPosts(int $offset, int $limit): array
    {
        // hier später Repository
        return [];
    }
}
