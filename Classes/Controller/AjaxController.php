<?php

namespace Lanius\Forumman\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class AjaxController
{
    public static function editPost(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $postId = (int)($data['postId'] ?? 0);
        $content = trim($data['content'] ?? '');

        /** @var \Lanius\Forumman\Domain\Repository\PostsRepository $postsRepository */
        $postsRepository = GeneralUtility::makeInstance(
            \Lanius\Forumman\Domain\Repository\PostsRepository::class
        );

        $post = $postsRepository->findByUid($postId);

        if (!$post) {
            echo json_encode(['success' => false]);
            exit;
        }

        // 🔒 Security Check
        $currentUserId = $this->getFrontendUserId();

        if ($post->getUser()->getUid() !== $currentUserId) {
            http_response_code(403);
            echo json_encode(['success' => false]);
            exit;
        }

        // speichern
        $post->setContent($content);
        $postsRepository->update($post);

        echo json_encode(['success' => true]);
        exit;
    }

    protected function getFrontendUserId(): int
    {
        $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        return (int)$context->getAspect('frontend.user')->get('id');
    }
}
