<?php

declare(strict_types=1);

namespace Lanius\Forumman\Command;

use TYPO3\CMS\Core\Database\ConnectionPool;
use Lanius\Forumman\Domain\Service\PostIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lanius\Forumman\Domain\Service\UserIndexer;

class ReindexCommand extends Command
{

    public function __construct(
        protected ConnectionPool $connectionPool,
        protected PostIndexer $postIndexer
    ) {
        parent::__construct();
    }


    protected UserIndexer $userIndexer;

    public function injectUserIndexer(UserIndexer $userIndexer): void
    {
        $this->userIndexer = $userIndexer;
    }

    protected function configure(): void
    {
        $this->setDescription('Reindex all forum posts into Elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // -----------------------------
        // POSTS INDEXIEREN
        // -----------------------------
        $connection = $this->connectionPool->getConnectionForTable('tx_forumman_domain_model_posts');

        $rows = $connection->select(
            ['*'],
            'tx_forumman_domain_model_posts',
            ['parent' => 0]
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $this->postIndexer->indexPost($row);
        }

        $output->writeln('Posts indexed: ' . count($rows));


        // -----------------------------
        // USERS INDEXIEREN 
        // -----------------------------
        $userConnection = $this->connectionPool->getConnectionForTable('fe_users');

        $users = $userConnection->select(
            ['*'],
            'fe_users'
        )->fetchAllAssociative();

        foreach ($users as $user) {
            $this->userIndexer->indexUser($user);
        }

        $output->writeln('Users indexed: ' . count($users));


        return Command::SUCCESS;
    }
}
