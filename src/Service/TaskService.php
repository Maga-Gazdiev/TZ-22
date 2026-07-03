<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Task;
use App\Repository\TaskRepository;
use App\Search\ElasticsearchClient;

class TaskService
{
  public function __construct(
    private TaskRepository $repository,
    private ElasticsearchClient $search,
  ) {
    $this->search->ensureIndex();
  }

  public function create(array $data): array
  {
    $this->validateCreate($data);

    $createDate = $data['create_date'] ?? date('Y-m-d\TH:i:s');

    $taskData = [
      'title' => trim($data['title']),
      'description' => $data['description'] ?? null,
      'due_date' => $this->normalizeDate($data['due_date']),
      'create_date' => $this->normalizeDate($createDate),
      'status' => $data['status'] ?? Task::STATUS_PENDING,
      'priority' => $data['priority'] ?? Task::PRIORITY_MEDIUM,
      'category' => $data['category'] ?? 'Личное',
    ];

    $id = $this->repository->create($taskData);

    $task = $this->repository->findById($id);
    if ($task) {
      $this->search->indexTask($id, $task->toArray());
    }

    return ['id' => $id, 'message' => 'Task created successfully'];
  }

  public function getList(array $params): array
  {
    $search = trim($params['search'] ?? '');
    $sort = $params['sort'] ?? 'due_date';
    $page = max(1, (int) ($params['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($params['per_page'] ?? 10)));

    if ($search !== '') {
      $searchResult = $this->search->searchByTitle($search, $page, $perPage);
      $tasks = $this->repository->findByIds($searchResult['ids'], $sort);

      return [
        'data' => array_map(fn(Task $t) => $t->toArray(), $tasks),
        'pagination' => [
          'page' => $searchResult['page'],
          'per_page' => $searchResult['per_page'],
          'total' => $searchResult['total'],
          'total_pages' => $searchResult['total_pages'],
        ],
      ];
    }

    $result = $this->repository->findAll($sort, $page, $perPage);

    return [
      'data' => array_map(fn(Task $t) => $t->toArray(), $result['items']),
      'pagination' => [
        'page' => $result['page'],
        'per_page' => $result['per_page'],
        'total' => $result['total'],
        'total_pages' => $result['total_pages'],
      ],
    ];
  }

  public function getById(int $id): ?array
  {
    $task = $this->repository->findById($id);
    return $task ? $task->toArray() : null;
  }

  public function update(int $id, array $data): void
  {
    $existing = $this->repository->findById($id);
    if (!$existing) {
      throw new \RuntimeException('Задача не найдена', 404);
    }

    $updateData = [];

    if (isset($data['title'])) {
      $title = trim($data['title']);
      if ($title === '' || mb_strlen($title) > 255) {
        throw new \InvalidArgumentException('Название обязательно и не более 255 символов');
      }
      $updateData['title'] = $title;
    }

    if (array_key_exists('description', $data)) {
      $updateData['description'] = $data['description'];
    }

    if (isset($data['due_date'])) {
      $updateData['due_date'] = $this->normalizeDate($data['due_date']);
    }

    if (isset($data['status'])) {
      $this->assertIn($data['status'], Task::STATUSES, 'status');
      $updateData['status'] = $data['status'];
    }

    if (isset($data['priority'])) {
      $this->assertIn($data['priority'], Task::PRIORITIES, 'priority');
      $updateData['priority'] = $data['priority'];
    }

    if (isset($data['category'])) {
      $updateData['category'] = $data['category'];
    }

    if (empty($updateData)) {
      throw new \InvalidArgumentException('Нет данных для обновления');
    }

    $this->repository->update($id, $updateData);

    $task = $this->repository->findById($id);
    if ($task) {
      $this->search->indexTask($id, $task->toArray());
    }
  }

  public function delete(int $id): void
  {
    $existing = $this->repository->findById($id);
    if (!$existing) {
      throw new \RuntimeException('Задача не найдена', 404);
    }

    $this->repository->delete($id);
    $this->search->deleteTask($id);
  }

  private function validateCreate(array $data): void
  {
    if (empty($data['title']) || mb_strlen(trim($data['title'])) > 255) {
      throw new \InvalidArgumentException('Название обязательно и не более 255 символов');
    }

    if (empty($data['due_date'])) {
      throw new \InvalidArgumentException('Срок выполнения обязателен');
    }

    if (isset($data['status'])) {
      $this->assertIn($data['status'], Task::STATUSES, 'status');
    }

    if (isset($data['priority'])) {
      $this->assertIn($data['priority'], Task::PRIORITIES, 'priority');
    }
  }

  private function assertIn(string $value, array $allowed, string $field): void
  {
    if (!in_array($value, $allowed, true)) {
      throw new \InvalidArgumentException("Недопустимое значение поля {$field}");
    }
  }

  private function normalizeDate(string $date): string
  {
    $dt = new \DateTime($date);
    return $dt->format('Y-m-d H:i:s');
  }
}
