<?php

declare(strict_types=1);

namespace App\Search;

use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchClient
{
  private $client;
  private string $index;

  public function __construct()
  {
    $host = sprintf(
      'http://%s:%s',
      $_ENV['ELASTICSEARCH_HOST'],
      $_ENV['ELASTICSEARCH_PORT']
    );

    $this->client = ClientBuilder::create()
      ->setHosts([$host])
      ->build();

    $this->index = $_ENV['ELASTICSEARCH_INDEX'] ?? 'tasks';
  }

  public function ensureIndex(): void
  {
    if (!$this->client->indices()->exists(['index' => $this->index])->asBool()) {
      $this->client->indices()->create([
        'index' => $this->index,
        'body' => [
          'mappings' => [
            'properties' => [
              'title' => ['type' => 'text'],
              'description' => ['type' => 'text'],
              'due_date' => ['type' => 'date'],
              'create_date' => ['type' => 'date'],
              'status' => ['type' => 'keyword'],
              'priority' => ['type' => 'keyword'],
              'category' => ['type' => 'keyword'],
            ],
          ],
        ],
      ]);
    }
  }

  public function indexTask(int $id, array $data): void
  {
    $this->client->index([
      'index' => $this->index,
      'id' => (string) $id,
      'body' => $data,
    ]);
  }

  public function deleteTask(int $id): void
  {
    try {
      $this->client->delete([
        'index' => $this->index,
        'id' => (string) $id,
      ]);
    } catch (\Throwable) {
    }
  }

  public function searchByTitle(string $query, int $page = 1, int $perPage = 10): array
  {
    $from = ($page - 1) * $perPage;

    $response = $this->client->search([
      'index' => $this->index,
      'body' => [
        'from' => $from,
        'size' => $perPage,
        'query' => [
          'match' => [
            'title' => $query,
          ],
        ],
        'sort' => [
          ['due_date' => ['order' => 'asc']],
        ],
      ],
    ]);

    $hits = $response['hits']['hits'] ?? [];
    $total = $response['hits']['total']['value'] ?? 0;

    $ids = array_map(fn($hit) => (int) $hit['_id'], $hits);

    return [
      'ids' => $ids,
      'total' => $total,
      'page' => $page,
      'per_page' => $perPage,
      'total_pages' => (int) ceil($total / $perPage),
    ];
  }
}
