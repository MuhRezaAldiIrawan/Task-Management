# Task Management Platform

A full-stack Task Management Platform built with Laravel 13 REST API and Next.js frontend.

## 🏗️ Architecture

```
Task Management/
├── backend/          # Laravel 13 REST API
├── frontend/         # Next.js (coming soon)
├── docs/             # Documentation
│   ├── postman/      # Postman Collection
│   └── api-docs.json # OpenAPI Specification
└── docker-compose.yml
```

## 🚀 Quick Start

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Setup environment
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan storage:link
php artisan queue:table && php artisan migrate

# Start server
php artisan serve --port=8000
```

### Using Docker

```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec backend php artisan migrate:fresh --seed
```

Access services:

- **API**: http://localhost:8000
- **PHPMyAdmin**: http://localhost:8080
- **MySQL**: localhost:3307
- **Redis**: localhost:6379

## 📚 API Documentation

### Swagger UI (Interactive)

**http://localhost:8000/api/documentation**

Features:

- Interactive "Try it out" functionality
- JWT token authentication
- Request/response examples
- Full endpoint documentation

### OpenAPI JSON

**http://localhost:8000/docs**

Static file: `docs/api-docs.json`

### Postman Collection

Import: `docs/postman/Task-Management-API.postman_collection.json`

## 🔐 Authentication

All protected endpoints require JWT token:

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Use token in requests
curl -X GET http://localhost:8000/api/auth/tasks \
  -H "Authorization: Bearer {token}"
```

## 📡 API Endpoints

### Authentication

| Method | Endpoint            | Description      |
| ------ | ------------------- | ---------------- |
| POST   | `/api/auth/login`   | Login            |
| POST   | `/api/auth/logout`  | Logout           |
| GET    | `/api/auth/me`      | Get current user |
| POST   | `/api/auth/refresh` | Refresh token    |

### Tasks

| Method | Endpoint               | Description |
| ------ | ---------------------- | ----------- |
| GET    | `/api/auth/tasks`      | List tasks  |
| POST   | `/api/auth/tasks`      | Create task |
| GET    | `/api/auth/tasks/{id}` | Get task    |
| PUT    | `/api/auth/tasks/{id}` | Update task |
| DELETE | `/api/auth/tasks/{id}` | Delete task |

### Comments

| Method | Endpoint                        | Description    |
| ------ | ------------------------------- | -------------- |
| GET    | `/api/auth/tasks/{id}/comments` | List comments  |
| POST   | `/api/auth/tasks/{id}/comments` | Add comment    |
| PUT    | `/api/auth/comments/{id}`       | Update comment |
| DELETE | `/api/auth/comments/{id}`       | Delete comment |

### Attachments

| Method | Endpoint                               | Description   |
| ------ | -------------------------------------- | ------------- |
| GET    | `/api/auth/tasks/{id}/attachments`     | List files    |
| POST   | `/api/auth/tasks/{id}/attachments`     | Upload file   |
| GET    | `/api/auth/attachments/{id}/download`  | Download      |
| GET    | `/api/auth/attachments/{id}/thumbnail` | Get thumbnail |
| DELETE | `/api/auth/attachments/{id}`           | Delete file   |

## 🧪 Test Credentials

| Role    | Email               | Password |
| ------- | ------------------- | -------- |
| Admin   | admin@example.com   | password |
| Manager | manager@example.com | password |
| User    | john@example.com    | password |

## 🛠️ Technology Stack

### Backend

- **Laravel 13** - PHP Framework
- **JWT Auth** - Authentication
- **Redis** - Caching
- **MySQL 8** - Database
- **Intervention Image** - Image Processing
- **L5-Swagger** - API Documentation

### Features

- RESTful API design
- Redis caching with TTL
- Background jobs for emails
- File upload with thumbnail generation
- Docker Compose setup

## 📁 Documentation

| File                                            | Description              |
| ----------------------------------------------- | ------------------------ |
| `backend/README.md`                             | Backend setup & API docs |
| `docs/README.md`                                | API documentation        |
| `docs/api-docs.json`                            | OpenAPI spec             |
| `docs/postman/*.json`                           | Postman collection       |
| `2026-08-13-task-management-platform-design.md` | Design document          |

demo link

https://drive.google.com/file/d/1yobDuuIKqjw0q2nhky9KEFZysI8XM568/view?usp=sharing

## 📄 License

MIT License
