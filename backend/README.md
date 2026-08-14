# Task Management Backend

Laravel 13 REST API for Task Management Platform with JWT Authentication, Redis Caching, and Docker support.

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Composer
- MySQL 8.0
- Redis
- Node.js 18+ (for frontend)

### Local Development Setup

```bash
# 1. Install dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Generate JWT secret
php artisan jwt:secret

# 5. Create database and run migrations
php artisan migrate:fresh --seed

# 6. Create storage symlink
php artisan storage:link

# 7. Setup queue table (for background jobs)
php artisan queue:table && php artisan migrate

# 8. Start the development server
php artisan serve --port=8000
```

The API will be available at `http://localhost:8000`

---

## 📚 API Documentation

### Swagger UI (Interactive)
Access the interactive API documentation at:
```
http://localhost:8000/api/documentation
```

Features:
- Interactive "Try it out" functionality
- JWT token authentication
- Request/response examples
- Full endpoint documentation

### OpenAPI JSON
Download or view the raw OpenAPI specification:
```
http://localhost:8000/docs
```

Static file: `storage/api-docs/api-docs.json`

---

## 🐳 Docker Setup

### Using Docker Compose (Recommended)

```bash
# Start all services (MySQL, Redis, PHPMyAdmin)
docker-compose up -d

# Run migrations inside container
docker-compose exec backend php artisan migrate:fresh --seed

# View logs
docker-compose logs -f
```

### Docker Services
| Service | Port | Description |
|---------|------|-------------|
| Backend | 8000 | Laravel API |
| MySQL | 3307 | Database |
| Redis | 6379 | Cache & Queue |
| PHPMyAdmin | 8080 | Database GUI |

Access PHPMyAdmin at: `http://localhost:8080`
- Server: mysql
- Username: root
- Password: rootpassword

### Environment Variables for Docker
Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=rootpassword

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

---

## 📮 Postman Collection

Import the Postman collection for easy API testing:

**File:** `docs/postman/Task-Management-API.postman_collection.json`

### Setup in Postman

1. Import the collection from `docs/postman/`
2. The collection includes:
   - Pre-configured environment variables
   - Bearer token authentication
   - Example requests for all endpoints

### Environment Variables
| Variable | Value | Description |
|----------|-------|-------------|
| `baseUrl` | `http://localhost:8000/api` | API base URL |
| `token` | (auto-filled) | JWT token after login |
| `taskId` | (auto-filled) | Task ID for tests |
| `commentId` | (auto-filled) | Comment ID for tests |

---

## 🔐 Authentication

All protected endpoints require JWT token in the Authorization header:

```
Authorization: Bearer {token}
```

### Login Flow
```bash
# 1. Login to get token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# 2. Use the token in subsequent requests
curl -X GET http://localhost:8000/api/auth/tasks \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..."
```

---

## 📡 API Endpoints

### Authentication (`/api/auth/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | User login, returns JWT token |
| POST | `/api/auth/logout` | Invalidate current token |
| GET | `/api/auth/me` | Get current user details |
| POST | `/api/auth/refresh` | Refresh JWT token |

### Tasks (`/api/auth/tasks/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/tasks` | List tasks (paginated, filterable) |
| POST | `/api/auth/tasks` | Create new task |
| GET | `/api/auth/tasks/{id}` | Get task details |
| PUT | `/api/auth/tasks/{id}` | Update task |
| DELETE | `/api/auth/tasks/{id}` | Delete task |

**Query Parameters for List:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)
- `status` - Filter by status (pending, in_progress, completed, cancelled)
- `priority` - Filter by priority (low, medium, high, urgent)
- `assigned_user_id` - Filter by assignee
- `sort_by` - Sort field (created_at, title, status, priority, etc.)
- `sort_order` - Sort direction (asc, desc)
- `search` - Search in title and description

### Comments (`/api/auth/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/tasks/{taskId}/comments` | List comments for task |
| POST | `/api/auth/tasks/{taskId}/comments` | Add comment |
| PUT | `/api/auth/comments/{commentId}` | Update comment |
| DELETE | `/api/auth/comments/{commentId}` | Delete comment |

### Attachments (`/api/auth/`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/tasks/{taskId}/attachments` | List attachments |
| POST | `/api/auth/tasks/{taskId}/attachments` | Upload file |
| GET | `/api/auth/attachments/{id}/download` | Download file |
| GET | `/api/auth/attachments/{id}/thumbnail` | Get image thumbnail |
| DELETE | `/api/auth/attachments/{id}` | Delete file |

**Upload File:**
```bash
curl -X POST http://localhost:8000/api/auth/tasks/1/attachments \
  -H "Authorization: Bearer {token}" \
  -F "file=@/path/to/file.pdf"
```

**Supported File Types:**
- Images: JPEG, PNG, GIF, WebP, SVG
- Documents: PDF, DOC, DOCX, XLS, XLSX
- Text: TXT, CSV
- Video: MP4, WebM
- Archives: ZIP
- **Max size:** 50MB

---

## 🧪 Test Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Manager | manager@example.com | password |
| User | john@example.com | password |

---

## ⚙️ Configuration

### Environment Variables

Key variables in `.env`:

```env
# Application
APP_NAME="Task Management"
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

# Database (Local)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=rootpassword

# Redis Cache
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis

# JWT
JWT_SECRET=your-secret-key
JWT_TTL=60  # Token validity in minutes

# Queue
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# File Storage
FILESYSTEM_DISK=public
```

### Redis Caching

The API uses Redis for caching:
- Task list: 2 minutes TTL
- Task details: 5 minutes TTL
- Cache is automatically invalidated on data changes

### Queue Workers

For background jobs (email notifications):

```bash
# Start queue worker
php artisan queue:work

# Or use Redis as broker
php artisan queue:work redis
```

---

## 🧰 Useful Commands

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Regenerate Swagger docs
php artisan l5-swagger:generate

# Run migrations
php artisan migrate
php artisan migrate:fresh --seed

# Create API resource
php artisan make:resource UserResource
php artisan make:request StoreUserRequest

# Run tests
php artisan test

# Database seeding
php artisan db:seed

# Queue monitoring (requires Horizon)
php artisan horizon
```

---

## 📁 Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/          # API Controllers
│   │   │   └── AuthController.php
│   │   ├── Middleware/
│   │   └── Requests/         # Form Requests
│   ├── Models/               # Eloquent Models
│   ├── Services/             # Business Logic
│   ├── Events/               # Event Classes
│   ├── Jobs/                 # Queue Jobs
│   └── Documentation/        # OpenAPI Specs
├── config/                   # Laravel Config
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── routes/
│   └── api.php              # API Routes
├── storage/
│   └── api-docs/           # Generated Swagger docs
└── tests/                   # Test Files
```

---

## 🛠️ Troubleshooting

### Redis Connection Error
```
Class "Redis" not found
```
**Fix:** Change `REDIS_CLIENT=phpredis` to `REDIS_CLIENT=predis` in `.env`, then run:
```bash
composer require predis/predis
```

### Storage Link Error
```bash
php artisan storage:link
```

### Generate API Docs
```bash
php artisan l5-swagger:generate
```

### Docker MySQL Connection
If using Docker, ensure `DB_HOST=mysql` (container name).

---

## 📄 License

MIT License - See LICENSE file for details.
