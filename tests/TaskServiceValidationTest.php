<?php

declare(strict_types=1);

namespace Tests;

use App\Service\TaskService;
use App\Repository\TaskRepository;
use App\Search\ElasticsearchClient;
use PHPUnit\Framework\TestCase;

class TaskServiceValidationTest extends TestCase
{
  private TaskService $service;

  protected function setUp(): void
  {
    $repo = $this->createMock(TaskRepository::class);
    $search = $this->createMock(ElasticsearchClient::class);

    $search->method('ensureIndex');
    $this->service = new TaskService($repo, $search);
  }

  public function testCreateWithoutTitle(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->create(['due_date' => '2025-01-20T15:00:00']);
  }

  public function testCreateWithoutDueDate(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->create(['title' => 'Задача']);
  }

  public function testCreateWithInvalidStatus(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->create([
      'title' => 'Задача',
      'due_date' => '2025-01-20T15:00:00',
      'status' => 'unknown',
    ]);
  }
}
