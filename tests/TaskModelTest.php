<?php

declare(strict_types=1);

namespace Tests;

use App\Model\Task;
use PHPUnit\Framework\TestCase;

class TaskModelTest extends TestCase
{
  public function testToArray(): void
  {
    $task = new Task(
      1,
      'Тестовая задача',
      'описание',
      '2025-01-20 15:00:00',
      '2025-01-20 10:00:00',
      Task::STATUS_PENDING,
      Task::PRIORITY_HIGH,
      'Работа'
    );

    $arr = $task->toArray();

    $this->assertEquals(1, $arr['id']);
    $this->assertEquals('Тестовая задача', $arr['title']);
    $this->assertEquals('2025-01-20T15:00:00', $arr['due_date']);
    $this->assertEquals('не выполнена', $arr['status']);
  }

  public function testFromRow(): void
  {
    $row = [
      'id' => 5,
      'title' => 'Задача',
      'description' => null,
      'due_date' => '2025-06-01 12:00:00',
      'create_date' => '2025-05-01 09:00:00',
      'status' => 'выполнена',
      'priority' => 'низкий',
      'category' => 'Дом',
    ];

    $task = Task::fromRow($row);
    $this->assertEquals(5, $task->id);
    $this->assertEquals('Дом', $task->category);
  }
}
