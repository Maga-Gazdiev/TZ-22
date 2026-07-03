<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\Model\Task;
use PDO;

class TaskRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getConnection();
  }

  public function create(array $data): int
  {
    $sql = 'INSERT INTO tasks (title, description, due_date, create_date, status, priority, category)
            VALUES (:title, :description, :due_date, :create_date, :status, :priority, :category)
            RETURNING id';

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      'title' => $data['title'],
      'description' => $data['description'] ?? null,
      'due_date' => $data['due_date'],
      'create_date' => $data['create_date'],
      'status' => $data['status'],
      'priority' => $data['priority'],
      'category' => $data['category'],
    ]);

    return (int) $stmt->fetchColumn();
  }

  public function findById(int $id): ?Task
  {
    $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ? Task::fromRow($row) : null;
  }

  public function findAll(string $sort = 'due_date', int $page = 1, int $perPage = 10): array
  {
    $allowedSort = ['due_date', 'created_at'];
    if (!in_array($sort, $allowedSort, true)) {
      $sort = 'due_date';
    }

    $orderColumn = $sort === 'created_at' ? 'create_date' : 'due_date';
    $offset = ($page - 1) * $perPage;

    $countStmt = $this->db->query('SELECT COUNT(*) FROM tasks');
    $total = (int) $countStmt->fetchColumn();

    $sql = "SELECT * FROM tasks ORDER BY {$orderColumn} ASC LIMIT :limit OFFSET :offset";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $tasks = [];
    while ($row = $stmt->fetch()) {
      $tasks[] = Task::fromRow($row);
    }

    return [
      'items' => $tasks,
      'total' => $total,
      'page' => $page,
      'per_page' => $perPage,
      'total_pages' => (int) ceil($total / $perPage),
    ];
  }

  public function findByIds(array $ids, string $sort = 'due_date'): array
  {
    if (empty($ids)) {
      return [];
    }

    $orderColumn = $sort === 'created_at' ? 'create_date' : 'due_date';
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT * FROM tasks WHERE id IN ({$placeholders}) ORDER BY {$orderColumn} ASC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(array_values($ids));

    $tasks = [];
    while ($row = $stmt->fetch()) {
      $tasks[] = Task::fromRow($row);
    }

    return $tasks;
  }

  public function update(int $id, array $data): bool
  {
    $fields = [];
    $params = ['id' => $id];

    $allowed = ['title', 'description', 'due_date', 'status', 'priority', 'category'];
    foreach ($allowed as $field) {
      if (array_key_exists($field, $data)) {
        $fields[] = "{$field} = :{$field}";
        $params[$field] = $data[$field];
      }
    }

    if (empty($fields)) {
      return false;
    }

    $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $this->db->prepare($sql);

    return $stmt->execute($params);
  }

  public function delete(int $id): bool
  {
    $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
    return $stmt->execute(['id' => $id]);
  }
}
