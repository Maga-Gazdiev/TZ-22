<?php

declare(strict_types=1);

use App\Handler\TaskHandler;
use App\Repository\TaskRepository;
use App\Search\ElasticsearchClient;
use App\Service\TaskService;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->add(function (Request $request, $handler) {
  $response = $handler->handle($request);
  return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$repository = new TaskRepository();
$search = new ElasticsearchClient();
$service = new TaskService($repository, $search);
$handler = new TaskHandler($service);

$app->options('/{routes:.+}', function (Request $request, Response $response) {
  return $response;
});

$app->get('/api/docs', function (Request $request, Response $response) {
  $html = file_get_contents(__DIR__ . '/swagger.html');
  $response->getBody()->write($html);
  return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/api/openapi.yaml', function (Request $request, Response $response) {
  $yaml = file_get_contents(__DIR__ . '/openapi.yaml');
  $response->getBody()->write($yaml);
  return $response->withHeader('Content-Type', 'application/x-yaml');
});

$app->get('/health', function (Request $request, Response $response) {
  $response->getBody()->write(json_encode(['status' => 'ok']));
  return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/tasks', [$handler, 'create']);
$app->get('/api/tasks', [$handler, 'list']);
$app->get('/api/tasks/{id}', [$handler, 'get']);
$app->put('/api/tasks/{id}', [$handler, 'update']);
$app->delete('/api/tasks/{id}', [$handler, 'delete']);

$app->run();
