<?php

declare(strict_types=1);

namespace App\Model;

class Task
{
  public const STATUS_DONE = 'выполнена';
  public const STATUS_PENDING = 'не выполнена';

  public const PRIORITY_LOW = 'низкий';
  public const PRIORITY_MEDIUM = 'средний';
  public const PRIORITY_HIGH = 'высокий';

  public const STATUSES = [self::STATUS_DONE, self::STATUS_PENDING];
  public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH];

  public function __construct(
    public ?int $id,
    public string $title,
    public ?string $description,
    public string $dueDate,
    public string $createDate,
    public string $status,
    public string $priority,
    public string $category,
  ) {}

  public function toArray(): array
  {
    return [
      'id' => $this->id,
      'title' => $this->title,
      'description' => $this->description,
      'due_date' => $this->formatDate($this->dueDate),
      'create_date' => $this->formatDate($this->createDate),
      'status' => $this->status,
      'priority' => $this->priority,
      'category' => $this->category,
    ];
  }

  private function formatDate(string $date): string
  {
    $dt = new \DateTime($date);
    return $dt->format('Y-m-d\TH:i:s');
  }

  public static function fromRow(array $row): self
  {
    return new self(
      (int) $row['id'],
      $row['title'],
      $row['description'],
      $row['due_date'],
      $row['create_date'],
      $row['status'],
      $row['priority'],
      $row['category'],
    );
  }
}
