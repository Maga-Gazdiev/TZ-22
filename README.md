# Tasks API

REST API для управления списком задач на PHP.

## Стек

- PHP 8.2, Slim Framework
- PostgreSQL - хранение задач
- Elasticsearch - поиск по названию
- Docker

## Архитектура

```
src/
  Handler/     - HTTP-слой (запросы/ответы)
  Service/     - бизнес-логика
  Repository/  - работа с PostgreSQL
  Search/      - Elasticsearch
  Model/       - модель задачи
  Config/      - подключение к БД
```

## Переменные окружения

Скопировать `.env.example` в `.env` и заполнить:

| Переменная | Формат | Описание |
|------------|--------|----------|
| `APP_ENV` | `local` / `production` | окружение |
| `APP_PORT` | число, напр. `8080` | порт приложения |
| `DB_HOST` | IP или hostname | хост PostgreSQL |
| `DB_PORT` | число, напр. `5432` | порт PostgreSQL |
| `DB_NAME` | строка | имя базы |
| `DB_USER` | строка | пользователь БД |
| `DB_PASS` | строка | пароль БД |
| `ELASTICSEARCH_HOST` | IP или hostname | хост Elasticsearch |
| `ELASTICSEARCH_PORT` | число, напр. `9200` | порт Elasticsearch |
| `ELASTICSEARCH_INDEX` | строка, напр. `tasks` | имя индекса |

## Запуск

```bash
cp .env.example .env
# заполнить .env
composer install
chmod +x scripts/migrate.sh
./scripts/migrate.sh
php -S 0.0.0.0:8080 -t public public/index.php
```

### Docker

```bash
docker compose up --build
```

## Swagger

Документация: `/api/docs`

## API

| Метод | URL | Описание |
|-------|-----|----------|
| POST | /api/tasks | Создание задачи |
| GET | /api/tasks | Список задач |
| GET | /api/tasks/{id} | Задача по ID |
| PUT | /api/tasks/{id} | Обновление |
| DELETE | /api/tasks/{id} | Удаление |

### Параметры GET /api/tasks

- `search` - поиск по названию (через Elasticsearch)
- `sort` - `due_date` или `created_at`
- `page` - номер страницы (по умолчанию 1)
- `per_page` - записей на странице (по умолчанию 10)

### Пример создания

```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Задача1",
    "description": "Задача1 описание",
    "due_date": "2025-01-20T15:00:00",
    "priority": "высокий",
    "category": "Работа",
    "status": "не выполнена"
  }'
```

### Пример списка с пагинацией

```bash
curl "http://localhost:8080/api/tasks?page=1&per_page=5&sort=due_date"
```

## Тестирование

### Unit-тесты (PHPUnit)

```bash
./vendor/bin/phpunit
```

### Ручное тестирование

1. **curl** - проверка всех эндпоинтов
2. **Swagger UI** - `/api/docs`
3. **Postman** - опционально

### Как тестировал

- PHPUnit - модель и валидация
- curl - интеграционная проверка API
- Swagger UI - проверка контрактов
- `/health` - проверка что сервис поднялся

## Health check

```bash
curl http://localhost:8080/health
```
# TZ-22
