<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TaskHandler
{
  public function __construct(private TaskService $service) {}

  public function create(Request $request, Response $response): Response
  {
    $body = (array) json_decode((string) $request->getBody(), true);

    try {
      $result = $this->service->create($body);
      return $this->json($response, $result, 201);
    } catch (\InvalidArgumentException $e) {
      return $this->json($response, ['error' => $e->getMessage()], 400);
    }
  }

  public function list(Request $request, Response $response): Response
  {
    $params = $request->getQueryParams();
    $result = $this->service->getList($params);

    if (!isset($params['page']) && !isset($params['per_page'])) {
      return $this->json($response, $result['data']);
    }

    return $this->json($response, $result);
  }

  public function get(Request $request, Response $response, array $args): Response
  {
    $id = (int) $args['id'];
    $task = $this->service->getById($id);

    if (!$task) {
      return $this->json($response, ['error' => 'Задача не найдена'], 404);
    }

    return $this->json($response, $task);
  }

  public function update(Request $request, Response $response, array $args): Response
  {
    $id = (int) $args['id'];
    $body = (array) json_decode((string) $request->getBody(), true);

    try {
      $this->service->update($id, $body);
      return $this->json($response, ['message' => 'Task updated successfully']);
    } catch (\RuntimeException $e) {
      $code = $e->getCode() >= 400 ? $e->getCode() : 500;
      return $this->json($response, ['error' => $e->getMessage()], $code);
    } catch (\InvalidArgumentException $e) {
      return $this->json($response, ['error' => $e->getMessage()], 400);
    }
  }

  public function delete(Request $request, Response $response, array $args): Response
  {
    $id = (int) $args['id'];

    try {
      $this->service->delete($id);
      return $this->json($response, ['message' => 'Task deleted successfully']);
    } catch (\RuntimeException $e) {
      $code = $e->getCode() >= 400 ? $e->getCode() : 500;
      return $this->json($response, ['error' => $e->getMessage()], $code);
    }
  }

  private function json(Response $response, mixed $data, int $status = 200): Response
  {
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($status);
  }
}
