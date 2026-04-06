<?php

declare(strict_types=1);

namespace Lanius\Forumman\Service;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ElasticsearchService
{
    protected Client $client;
    protected string $indexName;
    protected string $indexNameUsers;


    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration
    ) {
        $config = $this->extensionConfiguration->get('forumman');

        $this->indexName = $config['elasticsearchIndex'] ?? 'forumman_posts';
        $this->indexNameUsers = $config['elasticsearchIndexUsers'] ?? 'forumman_users';

        $url = $config['elasticsearchUrl'] ?? 'http://localhost:9200';

        $this->client = ClientBuilder::create()
            ->setHosts([$url])
            ->build();
    }

    /**
     * Search forum posts
     */
    public function search(string $query, int $page = 1, int $limit = 10): array
    {
        $from = ($page - 1) * $limit;

        $response = $this->client->search([
            'index' => [$this->indexName, $this->indexNameUsers],
            'body' => [
                'from' => $from,
                'size' => $limit,
                'query' => [
                    'bool' => [
                        'should' => [
                            // 🔵 POSTS
                            [
                                'bool' => [
                                    'must' => [
                                        [
                                            'multi_match' => [
                                                'query' => $query,
                                                'fields' => [
                                                    'title^3',
                                                    'content'
                                                ]
                                            ]
                                        ]
                                    ],
                                    'filter' => [
                                        ['term' => ['_index' => $this->indexName]]
                                    ]
                                ]
                            ],

                            // 🟢 USERS
                            [
                                'bool' => [
                                    'must' => [
                                        [
                                            'multi_match' => [
                                                'query' => $query,
                                                'fields' => [
                                                    'username^3',
                                                    'name',
                                                    'email',
                                                    'profilbeschreibung',
                                                    'company'
                                                ]
                                            ]
                                        ]
                                    ],
                                    'filter' => [
                                        ['term' => ['_index' => $this->indexNameUsers]]
                                    ]
                                ]
                            ]
                        ],
                        'minimum_should_match' => 1
                    ]
                ]
            ]
        ]);

        $hits = $response['hits']['hits'] ?? [];


        // normalisieren (wichtig für Controller!)
        $normalized = array_map(function ($hit) {

            //DebuggerUtility::var_dump($hit);

            $source = $hit['_source'] ?? [];
            $index = $hit['_index'] ?? '';

            return [
                //'type' => $source['type'] ?? 'post',
                'type' => $index === 'forumman_users' ? 'user' : 'post',
                'uid' => $source['uid'] ?? null,
                'forum' => $source['forum'] ?? null,
                'title' => $source['title'] ?? '',
                'content' => $source['content'] ?? '',
                'username' => $source['username'] ?? '',
                'name' => $source['name'] ?? '',
                'email' => $source['email'] ?? '',
                'crdate' => $source['crdate'] ?? 0,
                '_score' => $hit['_score'] ?? 0,
                'postCount' => $source['postCount'] ?? 0,
                'usergroup' => $source['usergroup'] ?? '',
            ];
        }, $hits);

        return [
            'hits' => $normalized,
            'total' => $response['hits']['total']['value'] ?? 0
        ];
    }



    /**
     * Index a single post
     */
    public function index(string $id, array $data): void
    {
        // nur Hauptposts indexieren
        if ((int)($data['parent'] ?? 0) !== 0) {
            return;
        }

        $this->client->index([
            'index' => $this->indexName,
            'id' => $id,
            'body' => [
                'uid' => $data['uid'],
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'user' => $data['user'] ?? '',
                'username' => $data['username'] ?? '',
                'crdate' => $data['crdate'],
                'forum' => $data['forum'],
                'forumname' => $data['forumname']
            ]
        ]);
    }



    public function indexUser(string $id, array $data): void
    {
        $this->client->index([
            'index' => $this->indexNameUsers,
            'id' => $id,
            'body' => [
                'uid' => (int)$data['uid'],
                'username' => $data['username'] ?? '',
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'city' => $data['city'] ?? '',
                'www' => $data['www'] ?? '',
                'profilbeschreibung' => $data['profilbeschreibung'] ?? '',
                'company' => $data['company'] ?? '',
                'crdate' => (int)($data['crdate'] ?? time()),
                'postCount' => (int)$data['postCount'],
                'usergroup' => $data['usergroup'] ?? '',
            ]
        ]);
    }



    /**
     * Delete a document
     */
    public function delete(string $id): void
    {
        $this->client->delete([
            'index' => $this->indexName,
            'id' => $id
        ]);
    }

    public function delete2(string $id): void
    {
        $this->client->delete([
            'index' => $this->indexNameUsers,
            'id' => $id
        ]);
    }

    /**
     * Check ES connection
     */
    public function ping(): bool
    {
        try {
            return $this->client->ping()->asBool();
        } catch (\Throwable) {
            return false;
        }
    }
}
