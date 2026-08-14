# API Documentation

## Swagger UI

Interactive API documentation is available at:

**http://localhost:8000/api/documentation**

The Swagger UI provides:
- Full API endpoint documentation
- Try-it-out functionality
- JWT authentication support
- Request/response examples

## OpenAPI JSON

Raw OpenAPI specification in JSON format:

**http://localhost:8000/docs**

Or download the static file:
- `docs/api-docs.json`

## Postman Collection

Import the Postman collection from:
- `docs/postman/Task-Management-API.postman_collection.json`

## API Overview

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | User login |
| POST | /api/auth/logout | User logout |
| GET | /api/auth/me | Get current user |
| POST | /api/auth/refresh | Refresh JWT token |

### Tasks
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/auth/tasks | List all tasks |
| POST | /api/auth/tasks | Create new task |
| GET | /api/auth/tasks/{id} | Get task details |
| PUT | /api/auth/tasks/{id} | Update task |
| DELETE | /api/auth/tasks/{id} | Delete task |

### Comments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/auth/tasks/{taskId}/comments | List comments |
| POST | /api/auth/tasks/{taskId}/comments | Add comment |
| PUT | /api/auth/comments/{commentId} | Update comment |
| DELETE | /api/auth/comments/{commentId} | Delete comment |

### Attachments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/auth/tasks/{taskId}/attachments | List attachments |
| POST | /api/auth/tasks/{taskId}/attachments | Upload file |
| GET | /api/auth/attachments/{id}/download | Download file |
| GET | /api/auth/attachments/{id}/thumbnail | Get thumbnail |
| DELETE | /api/auth/attachments/{id} | Delete attachment |

## Authentication

All protected endpoints require JWT token in the Authorization header:

```
Authorization: Bearer {token}
```

### Getting a Token

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

Response:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

## Test Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Manager | manager@example.com | password |
| User | john@example.com | password |
