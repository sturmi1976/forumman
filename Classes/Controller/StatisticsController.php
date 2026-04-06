<?php

namespace Lanius\Forumman\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Lanius\Forumman\Domain\Repository\StatisticsRepository;
use Psr\Http\Message\ResponseInterface;

final class StatisticsController extends ActionController
{
    public function __construct(
        private readonly StatisticsRepository $statisticsRepository
    ) {}

    public function indexAction(): ResponseInterface
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\RequestInterface $request */
        $request = $this->request;

        $language = $this->request->getAttribute('language');
        $languageId = $language->getLanguageId();

        if (isset($this->settings['topThreadsLimit'])) {
            $topThreadsLimit = $this->settings['topThreadsLimit'];
        } else {
            $topThreadsLimit = 0;
        }

        if (isset($this->settings['topUsersLimit'])) {
            $topUsersLimit = $this->settings['topUsersLimit'];
        } else {
            $topUsersLimit = 0;
        }

        if (isset($this->settings['showTopLastDaysLimit'])) {
            $showTopLastDaysLimit = $this->settings['showTopLastDaysLimit'];
        } else {
            $showTopLastDaysLimit = 0;
        }


        $options = (int)($this->settings['statisticsOptions'] ?? 0);


        $this->view->assignMultiple([
            'userCount' => $this->statisticsRepository->getUserCount(),
            'threadCount' => $this->statisticsRepository->getThreadCount(),
            'postCount' => $this->statisticsRepository->getPostCount(),
            'postsToday' => $this->statisticsRepository->getPostsToday(),
            'topThreads' => $this->statisticsRepository->getTopThreads((int)$topThreadsLimit, $languageId),
            'topUsers' => $this->statisticsRepository->getTopUsers((int)$topUsersLimit),
            'postsLast7Days' => $this->statisticsRepository->getPostsLast7Days((int)$showTopLastDaysLimit),
            'showGeneralStats' => ($options & 1) === 1,
            'showTodayPosts'   => ($options & 2) === 2,
        ]);

        return $this->htmlResponse();
    }
}
