# Task Management Backend

Laravel 13 REST API for Task Management Platform.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan storage:link
php artisan queue:table && php artisan migrate
php artisan serve --port=8000
```

## API Endpoints

### Authentication
- POST /api/auth/login
- POST /api/auth/logout
- GET /api/auth/me
- POST /api/auth/refresh

### Tasks
- GET /api/tasks
- POST /api/tasks
- GET /api/tasks/{id}
- PUT /api/tasks/{id}
- DELETE /api/tasks/{id}

### Comments
- GET /api/tasks/{task}/comments
- POST /api/tasks/{task}/comments
- PUT /api/comments/{id}
- DELETE /api/comments/{id}

### Attachments
- GET /api/tasks/{task}/attachments
- POST /api/tasks/{task}/attachments
- GET /api/attachments/{id}/download
- GET /api/attachments/{id}/thumbnail
- DELETE /api/attachments/{id}

## Test Credentials
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Manager | manager@example.com | password |
| User | john@example.com | password |
