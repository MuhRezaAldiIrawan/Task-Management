# Task Management Platform - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun Task Management Platform dengan Laravel (monolith) dan Next.js sesuai requirements assessment

**Architecture:** Single Laravel application dengan modular structure (bukan 3 microservices). Semua fitur dalam 1 app: Auth (JWT), Tasks CRUD, Comments, File Upload, Notifications (Queue + WebSocket/Polling), dan Realtime features. Frontend terpisah dengan Next.js yang berkomunikasi via REST API.

**Tech Stack:**
- Backend: Laravel 13 (PHP 8.2+), MySQL 8.0, Redis, JWT Auth (tymon/jwt-auth), Laravel Reverb/Pusher
- Frontend: Next.js 14 (App Router), TypeScript, Tailwind CSS
- Database: MySQL 8.0
- Cache/Queue: Redis
- Real-time: Laravel Reverb atau Polling fallback

## Global Constraints

- PHP Version: 8.2+
- Laravel Version: 13.x
- Next.js Version: 14.x (App Router)
- Database: MySQL 8.0
- Auth: JWT dengan tymon/jwt-auth
- File Upload Max: 50MB
- Image Thumbnail Size: 200x200px
- Commit Message: **TIDAK ADA co-author** - gunakan format: `git commit -m "description"` tanpa `-m "..." Co-Authored-By: ...`

---

## IMPORTANT: Git Commit Policy

**WAJIB DIIKUTI SETIAP KALI COMMIT:**

```
✅ BENAR:
git commit -m "feat: add user authentication"
git commit -m "fix: resolve task filtering bug"

❌ SALAH (TIDAK PERNAH PAKAI):
git commit -m "description" -m "Co-Authored-By: Claude <noreply@anthropic.com>"
```

**Alasan:** Project ini akan disubmit ke perusahaan - co-author AI dalam commit history tidak profesional.

---

## File Structure

### Backend (Laravel - Single App)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── TaskController.php
│   │   │   ├── CommentController.php
│   │   │   ├── AttachmentController.php
│   │   │   └── UserController.php
│   │   ├── Requests/
│   │   │   ├── LoginRequest.php
│   │   │   ├── StoreTaskRequest.php
│   │   │   ├── UpdateTaskRequest.php
│   │   │   └── StoreCommentRequest.php
│   │   ├── Resources/
│   │   │   ├── TaskResource.php
│   │   │   ├── TaskCollection.php
│   │   │   ├── CommentResource.php
│   │   │   └── UserResource.php
│   │   └── Middleware/
│   │       └── JwtMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Task.php
│   │   ├── TaskComment.php
│   │   └── Attachment.php
│   ├── Services/
│   │   ├── TaskService.php
│   │   ├── FileService.php
│   │   └── NotificationService.php
│   ├── Jobs/
│   │   ├── SendTaskAssignmentEmail.php
│   │   └── ProcessFileUpload.php
│   ├── Events/
│   │   ├── TaskUpdated.php
│   │   ├── CommentAdded.php
│   │   └── AttachmentAdded.php
│   └── Mail/
│       └── TaskAssignedMail.php
├── config/
│   ├── jwt.php
│   └── broadcasting.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_tasks_table.php
│   │   ├── create_task_comments_table.php
│   │   └── create_attachments_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       ├── TaskSeeder.php
│       ├── CommentSeeder.php
│       └── AttachmentSeeder.php
├── routes/api.php
└── storage/app/files/
```

### Frontend (Next.js)

```
frontend/
├── app/
│   ├── (auth)/
│   │   └── login/
│   │       └── page.tsx
│   ├── (dashboard)/
│   │   ├── layout.tsx
│   │   ├── page.tsx (dashboard)
│   │   └── tasks/
│   │       ├── page.tsx (list)
│   │       └── [id]/
│   │           └── page.tsx (detail)
│   └── api/
│       └── [...]
├── components/
│   ├── ui/ (reusable components)
│   ├── tasks/
│   │   ├── TaskCard.tsx
│   │   ├── TaskList.tsx
│   │   ├── TaskForm.tsx
│   │   └── TaskFilter.tsx
│   ├── comments/
│   │   ├── CommentList.tsx
│   │   └── CommentForm.tsx
│   └── layout/
│       ├── Sidebar.tsx
│       └── Header.tsx
├── lib/
│   ├── api.ts
│   ├── auth.ts
│   └── types.ts
└── types/
    └── index.ts
```

---

## Task List

### Task 1: Project Setup & Database Foundation

**Files:**
- Create: `backend/database/migrations/2024_01_01_000001_create_users_table.php`
- Create: `backend/database/migrations/2024_01_01_000002_create_tasks_table.php`
- Create: `backend/database/migrations/2024_01_01_000003_create_task_comments_table.php`
- Create: `backend/database/migrations/2024_01_01_000004_create_attachments_table.php`
- Create: `backend/database/seeders/DatabaseSeeder.php`
- Create: `backend/database/seeders/UserSeeder.php`
- Create: `backend/database/seeders/TaskSeeder.php`
- Create: `backend/database/seeders/CommentSeeder.php`

**Interfaces:**
- Consumes: Laravel framework, MySQL
- Produces: Database schema, seeders with 5 users, 15 tasks, 10 comments

- [ ] **Step 1: Create users table migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'manager', 'user'])->default('user');
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

- [ ] **Step 2: Create tasks table migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_user_id');
            $table->index('due_date');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
```

- [ ] **Step 3: Create task_comments table migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
            
            $table->index('task_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
```

- [ ] **Step 4: Create attachments table migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->unsigned();
            $table->string('mime_type', 100);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
            
            $table->index('task_id');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
```

- [ ] **Step 5: Create UserSeeder**

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => 'password', 'role' => 'admin'],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'password' => 'password', 'role' => 'manager'],
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'password' => 'password', 'role' => 'user'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password']),
                'role' => $user['role'],
            ]);
        }
    }
}
```

- [ ] **Step 6: Create TaskSeeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $tasks = [
            ['title' => 'Setup project structure', 'description' => 'Create initial folder structure and config', 'status' => 'completed', 'priority' => 'high'],
            ['title' => 'Design database schema', 'description' => 'Create ERD and migration files', 'status' => 'completed', 'priority' => 'high'],
            ['title' => 'Implement authentication', 'description' => 'JWT login/logout functionality', 'status' => 'in_progress', 'priority' => 'urgent'],
            ['title' => 'Create task CRUD API', 'description' => 'REST endpoints for tasks', 'status' => 'pending', 'priority' => 'high'],
            ['title' => 'Add file upload feature', 'description' => 'Implement attachment uploads', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'Implement comments system', 'description' => 'Task comments functionality', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'Setup real-time notifications', 'description' => 'WebSocket/SSE for updates', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Write API documentation', 'description' => 'Swagger/OpenAPI docs', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Frontend dashboard', 'description' => 'Main dashboard UI', 'status' => 'pending', 'priority' => 'high'],
            ['title' => 'Task list view', 'description' => 'Paginated task listing with filters', 'status' => 'pending', 'priority' => 'high'],
            ['title' => 'Task detail page', 'description' => 'Single task view with comments', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'File upload UI', 'description' => 'Drag-drop upload interface', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'User management', 'description' => 'Admin user management panel', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Performance optimization', 'description' => 'Caching and query optimization', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Testing', 'description' => 'Unit and integration tests', 'status' => 'pending', 'priority' => 'medium'],
        ];

        foreach ($tasks as $index => $task) {
            Task::create([
                'title' => $task['title'],
                'description' => $task['description'],
                'status' => $task['status'],
                'priority' => $task['priority'],
                'assigned_user_id' => $users->random()->id,
                'created_by' => $users->where('role', '!=', 'user')->random()->id,
                'due_date' => now()->addDays(rand(1, 30)),
            ]);
        }
    }
}
```

- [ ] **Step 7: Create CommentSeeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = Task::all();
        $users = User::all();

        $comments = [
            'Great progress on this task!',
            'Can we discuss the approach?',
            'Needs more attention.',
            'Completed the first phase.',
            'Blocked by dependency issue.',
            'Ready for review.',
            'Updated the requirements.',
            'Please review ASAP.',
            'Implementation looks good.',
            'Need clarification on specs.',
        ];

        foreach ($comments as $comment) {
            TaskComment::create([
                'task_id' => $tasks->random()->id,
                'user_id' => $users->random()->id,
                'comment' => $comment,
            ]);
        }
    }
}
```

- [ ] **Step 8: Update DatabaseSeeder**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TaskSeeder::class,
            CommentSeeder::class,
        ]);
    }
}
```

- [ ] **Step 9: Run migrations and seeders**

```bash
cd backend
php artisan migrate:fresh --seed
```

- [ ] **Step 10: Commit**

```bash
git add backend/database/migrations/ backend/database/seeders/
git commit -m "feat: add database migrations and seeders (5 users, 15 tasks, 10 comments)"
```

---

### Task 2: User Model & JWT Authentication

**Files:**
- Modify: `backend/app/Models/User.php`
- Modify: `backend/config/auth.php`
- Create: `backend/config/jwt.php`
- Modify: `backend/app/Http/Controllers/Api/AuthController.php`
- Create: `backend/app/Http/Requests/LoginRequest.php`
- Create: `backend/app/Http/Middleware/JwtMiddleware.php`
- Modify: `backend/routes/api.php`

**Interfaces:**
- Consumes: Users table, tymon/jwt-auth package
- Produces: JWT login, logout, me endpoints

- [ ] **Step 1: Install JWT auth package**

```bash
cd backend
composer require tymon/jwt-auth:^2.0
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

- [ ] **Step 2: Update User model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
        ];
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_user_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }
}
```

- [ ] **Step 3: Configure auth.php for JWT**

```php
<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

- [ ] **Step 4: Create LoginRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
```

- [ ] **Step 5: Create AuthController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource(auth()->user()),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }
}
```

- [ ] **Step 6: Create UserResource**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

- [ ] **Step 7: Create JwtMiddleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
            ], 401);
        }

        return $next($request);
    }
}
```

- [ ] **Step 8: Register middleware in bootstrap/app.php**

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

- [ ] **Step 9: Update api.php routes**

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});
```

- [ ] **Step 10: Test authentication endpoints**

```bash
# Test login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Test me (with token)
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

- [ ] **Step 11: Commit**

```bash
git add backend/app/Models/User.php backend/app/Http/Controllers/Api/AuthController.php backend/app/Http/Requests/ backend/app/Http/Middleware/JwtMiddleware.php backend/app/Http/Resources/UserResource.php backend/config/ backend/routes/api.php
git commit -m "feat: add JWT authentication with login, logout, and me endpoints"
```

---

### Task 3: Task CRUD API

**Files:**
- Modify: `backend/app/Models/Task.php`
- Create: `backend/app/Http/Controllers/Api/TaskController.php`
- Create: `backend/app/Http/Requests/StoreTaskRequest.php`
- Create: `backend/app/Http/Requests/UpdateTaskRequest.php`
- Create: `backend/app/Http/Resources/TaskResource.php`
- Create: `backend/app/Http/Resources/TaskCollection.php`
- Create: `backend/app/Services/TaskService.php`
- Modify: `backend/routes/api.php`

**Interfaces:**
- Consumes: Tasks table, Users table
- Produces: Task CRUD endpoints with pagination, filtering, sorting

- [ ] **Step 1: Update Task model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'assigned_user_id',
        'created_by',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', $filters['status']);
            $query->whereIn('status', $statuses);
        }

        if (!empty($filters['priority'])) {
            $priorities = is_array($filters['priority']) ? $filters['priority'] : explode(',', $filters['priority']);
            $query->whereIn('priority', $priorities);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_user_id', $filters['assigned_to']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query;
    }
}
```

- [ ] **Step 2: Create TaskService**

```php
<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function getTasks(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return Task::with(['assignedUser:id,name,email', 'creator:id,name,email'])
            ->filter($filters)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    public function createTask(array $data): Task
    {
        $data['created_by'] = auth()->id();
        return Task::create($data);
    }

    public function getTask(int $id): Task
    {
        return Task::with(['assignedUser:id,name,email', 'creator:id,name,email', 'comments.user', 'attachments'])
            ->findOrFail($id);
    }

    public function updateTask(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh(['assignedUser:id,name,email', 'creator:id,name,email']);
    }

    public function deleteTask(Task $task): bool
    {
        return $task->delete();
    }
}
```

- [ ] **Step 3: Create StoreTaskRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
```

- [ ] **Step 4: Create UpdateTaskRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
```

- [ ] **Step 5: Create TaskResource**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_user' => $this->whenLoaded('assignedUser', fn() => [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
                'email' => $this->assignedUser->email,
            ]),
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'due_date' => $this->due_date?->toDateString(),
            'comments_count' => $this->when($this->comments_count !== null, $this->comments_count),
            'attachments_count' => $this->when($this->attachments_count !== null, $this->attachments_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

- [ ] **Step 6: Create TaskCollection**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    public $collects = TaskResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }
}
```

- [ ] **Step 7: Create TaskController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService
    ) {}

    public function index(Request $request): TaskCollection
    {
        $tasks = $this->taskService->getTasks($request->all());
        return new TaskCollection($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => new TaskResource($task),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getTask($id);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = $this->taskService->getTask($id);
        $task = $this->taskService->updateTask($task, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => new TaskResource($task),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $task = $this->taskService->getTask($id);
        $this->taskService->deleteTask($task);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }
}
```

- [ ] **Step 8: Update api.php routes**

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});
```

- [ ] **Step 9: Test task endpoints**

```bash
# List tasks
curl -X GET "http://localhost:8000/api/tasks?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create task
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"New Task","description":"Test","priority":"high"}'

# Update task
curl -X PUT http://localhost:8000/api/tasks/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"completed"}'
```

- [ ] **Step 10: Commit**

```bash
git add backend/app/Models/Task.php backend/app/Http/Controllers/Api/TaskController.php backend/app/Http/Requests/ backend/app/Http/Resources/ backend/app/Services/TaskService.php backend/routes/api.php
git commit -m "feat: add Task CRUD API with pagination, filtering, and sorting"
```

---

### Task 4: Comment System API

**Files:**
- Modify: `backend/app/Models/TaskComment.php`
- Create: `backend/app/Http/Controllers/Api/CommentController.php`
- Create: `backend/app/Http/Requests/StoreCommentRequest.php`
- Create: `backend/app/Http/Requests/UpdateCommentRequest.php`
- Create: `backend/app/Http/Resources/CommentResource.php`
- Modify: `backend/routes/api.php`

**Interfaces:**
- Consumes: task_comments table, tasks table, users table
- Produces: Comment CRUD endpoints

- [ ] **Step 1: Create TaskComment model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    use HasFactory;

    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 2: Create StoreCommentRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

- [ ] **Step 3: Create UpdateCommentRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

- [ ] **Step 4: Create CommentResource**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

- [ ] **Step 5: Create CommentController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(int $taskId): AnonymousResourceCollection
    {
        $task = Task::findOrFail($taskId);
        $comments = $task->comments()->with('user:id,name,email')->latest()->get();

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);

        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated()['comment'],
        ]);

        $comment->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => new CommentResource($comment),
        ], 201);
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse
    {
        $comment = TaskComment::where('user_id', auth()->id())->findOrFail($commentId);
        $comment->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    public function destroy(int $commentId): JsonResponse
    {
        $comment = TaskComment::where('user_id', auth()->id())->findOrFail($commentId);
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }
}
```

- [ ] **Step 6: Update api.php routes**

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::apiResource('tasks', TaskController::class);
    
    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
});
```

- [ ] **Step 7: Commit**

```bash
git add backend/app/Models/TaskComment.php backend/app/Http/Controllers/Api/CommentController.php backend/app/Http/Requests/ backend/app/Http/Resources/CommentResource.php backend/routes/api.php
git commit -m "feat: add Comment CRUD API for tasks"
```

---

### Task 5: File Upload System

**Files:**
- Modify: `backend/app/Models/Attachment.php`
- Create: `backend/app/Http/Controllers/Api/AttachmentController.php`
- Create: `backend/app/Services/FileService.php`
- Modify: `backend/routes/api.php`
- Modify: `backend/config/filesystems.php`

**Interfaces:**
- Consumes: attachments table, storage disk
- Produces: File upload, download, delete endpoints with thumbnail generation

- [ ] **Step 1: Create Attachment model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
```

- [ ] **Step 2: Create FileService**

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'video/mp4',
        'video/webm',
        'application/zip',
    ];

    private const MAX_FILE_SIZE = 52428800; // 50MB

    public function __construct(
        private readonly ImageManager $imageManager
    ) {}

    public function upload(UploadedFile $file, int $taskId, int $userId): array
    {
        $this->validateFile($file);

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = $originalName . '_' . time() . '.' . $extension;
        
        $year = date('Y');
        $month = date('m');
        $uuid = uniqid();
        
        $path = "files/{$year}/{$month}/{$uuid}";
        
        Storage::disk('public')->putFileAs($path, $file, $filename);
        
        $fullPath = "{$path}/{$filename}";
        
        if ($this->isImageFile($file)) {
            $this->generateThumbnail($fullPath, $path);
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $fullPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'task_id' => $taskId,
            'uploaded_by' => $userId,
            'uploaded_at' => now(),
        ];
    }

    public function delete(string $filePath): bool
    {
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
            
            $pathInfo = pathinfo($filePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            
            return true;
        }
        return false;
    }

    public function getFullPath(string $relativePath): string
    {
        return Storage::disk('public')->path($relativePath);
    }

    public function getThumbnailPath(string $filePath): ?string
    {
        $pathInfo = pathinfo($filePath);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
        
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return $thumbnailPath;
        }
        
        return null;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new \InvalidArgumentException('File type not allowed');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed (50MB)');
        }
    }

    private function isImageFile(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    private function generateThumbnail(string $originalPath, string $basePath): void
    {
        $originalFullPath = Storage::disk('public')->path($originalPath);
        $thumbnailDir = Storage::disk('public')->path($basePath) . '/thumbnails';
        
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $pathInfo = pathinfo($originalPath);
        $thumbnailFullPath = $thumbnailDir . '/' . $pathInfo['basename'];

        $image = $this->imageManager->read($originalFullPath);
        $image->cover(200, 200)->save($thumbnailFullPath, 80);
    }
}
```

- [ ] **Step 3: Install intervention image package**

```bash
cd backend
composer require intervention/image
```

- [ ] **Step 4: Create AttachmentController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    public function index(int $taskId): JsonResponse
    {
        $attachments = Attachment::where('task_id', $taskId)
            ->with('uploader:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attachments,
        ]);
    }

    public function store(Request $request, int $taskId): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB
        ]);

        try {
            $file = $request->file('file');
            $uploadData = $this->fileService->upload($file, $taskId, auth()->id());
            
            $attachment = Attachment::create($uploadData);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $attachment,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
            ], 500);
        }
    }

    public function download(int $id): Response|JsonResponse
    {
        $attachment = Attachment::findOrFail($id);
        
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        $fullPath = $this->fileService->getFullPath($attachment->file_path);

        return response()->download($fullPath, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function thumbnail(int $id): Response|JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!$attachment->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'Not an image',
            ], 400);
        }

        $thumbnailPath = $this->fileService->getThumbnailPath($attachment->file_path);
        
        if (!$thumbnailPath) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail not found',
            ], 404);
        }

        return response()->file($thumbnailPath);
    }

    public function destroy(int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);
        
        $this->fileService->delete($attachment->file_path);
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
    }
}
```

- [ ] **Step 5: Update api.php routes**

```php
<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::apiResource('tasks', TaskController::class);
    
    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    
    Route::get('tasks/{task}/attachments', [AttachmentController::class, 'index']);
    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store']);
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::get('attachments/{attachment}/thumbnail', [AttachmentController::class, 'thumbnail']);
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);
});
```

- [ ] **Step 6: Ensure storage link exists**

```bash
cd backend
php artisan storage:link
```

- [ ] **Step 7: Test file upload**

```bash
# Upload file
curl -X POST http://localhost:8000/api/tasks/1/attachments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/file.jpg"

# Download file
curl -X GET http://localhost:8000/api/attachments/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o downloaded_file.jpg
```

- [ ] **Step 8: Commit**

```bash
git add backend/app/Models/Attachment.php backend/app/Http/Controllers/Api/AttachmentController.php backend/app/Services/FileService.php backend/routes/api.php backend/config/
git commit -m "feat: add file upload system with thumbnail generation"
```

---

### Task 6: Background Jobs & Notifications

**Files:**
- Create: `backend/app/Jobs/SendTaskAssignmentEmail.php`
- Create: `backend/app/Jobs/ProcessFileUpload.php`
- Create: `backend/app/Mail/TaskAssignedMail.php`
- Create: `backend/app/Events/TaskUpdated.php`
- Create: `backend/app/Events/CommentAdded.php`
- Create: `backend/app/Listeners/SendTaskNotification.php`
- Create: `backend/app/Providers/EventServiceProvider.php`
- Modify: `backend/routes/api.php`

**Interfaces:**
- Consumes: Queue connection, Mail
- Produces: Queue jobs for emails, file processing, real-time events

- [ ] **Step 1: Install queue package and configure**

```bash
cd backend
composer require illuminate/queue
```

- [ ] **Step 2: Create SendTaskAssignmentEmail job**

```php
<?php

namespace App\Jobs;

use App\Mail\TaskAssignedMail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskAssignmentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignedUser
    ) {}

    public function handle(): void
    {
        Mail::to($this->assignedUser->email)->send(
            new TaskAssignedMail($this->task, $this->assignedUser)
        );
    }
}
```

- [ ] **Step 3: Create TaskAssignedMail**

```php
<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Task Assigned: ' . $this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task-assigned',
            with: [
                'task' => $this->task,
                'user' => $this->user,
            ],
        );
    }
}
```

- [ ] **Step 4: Create email template**

```php
<?php

// Create: backend/resources/views/emails/task-assigned.blade.php

/*
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Task Assigned</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>
    
    <p>A new task has been assigned to you:</p>
    
    <div style="border: 1px solid #ddd; padding: 15px; margin: 15px 0;">
        <h3 style="margin-top: 0;">{{ $task->title }}</h3>
        <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $task->status)) }}</p>
        <p><strong>Priority:</strong> {{ ucfirst($task->priority) }}</p>
        @if($task->due_date)
        <p><strong>Due Date:</strong> {{ $task->due_date->format('M d, Y') }}</p>
        @endif
        @if($task->description)
        <p><strong>Description:</strong><br>{{ $task->description }}</p>
        @endif
    </div>
    
    <p>Log in to view and manage this task.</p>
    
    <p>Best regards,<br>Task Management System</p>
</body>
</html>
*/
```

- [ ] **Step 5: Create ProcessFileUpload job**

```php
<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Services\FileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Attachment $attachment
    ) {}

    public function handle(FileService $fileService): void
    {
        // Additional processing like virus scan simulation
        // For now, just log it
        \Log::info('Processing file: ' . $this->attachment->file_name);
    }
}
```

- [ ] **Step 6: Create TaskUpdated event**

```php
<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('task.' . $this->task->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }
}
```

- [ ] **Step 7: Create CommentAdded event**

```php
<?php

namespace App\Events;

use App\Models\TaskComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TaskComment $comment
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('task.' . $this->comment->task_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.added';
    }
}
```

- [ ] **Step 8: Update TaskService to dispatch jobs**

```php
<?php

namespace App\Services;

use App\Events\TaskUpdated;
use App\Jobs\SendTaskAssignmentEmail;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function getTasks(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return Task::with(['assignedUser:id,name,email', 'creator:id,name,email'])
            ->filter($filters)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    public function createTask(array $data): Task
    {
        $data['created_by'] = auth()->id();
        $task = Task::create($data);

        if (!empty($data['assigned_user_id'])) {
            $assignedUser = $task->assignedUser;
            if ($assignedUser) {
                SendTaskAssignmentEmail::dispatch($task, $assignedUser);
            }
        }

        TaskUpdated::dispatch($task, 'created');

        return $task;
    }

    public function getTask(int $id): Task
    {
        return Task::with(['assignedUser:id,name,email', 'creator:id,name,email', 'comments.user', 'attachments'])
            ->findOrFail($id);
    }

    public function updateTask(Task $task, array $data): Task
    {
        $wasAssignedTo = $task->assigned_user_id;
        
        $task->update($data);
        $task = $task->fresh(['assignedUser:id,name,email', 'creator:id,name,email']);

        // Send notification if newly assigned
        if (!empty($data['assigned_user_id']) && $data['assigned_user_id'] !== $wasAssignedTo) {
            $assignedUser = $task->assignedUser;
            if ($assignedUser) {
                SendTaskAssignmentEmail::dispatch($task, $assignedUser);
            }
        }

        TaskUpdated::dispatch($task, 'updated');

        return $task;
    }

    public function deleteTask(Task $task): bool
    {
        TaskUpdated::dispatch($task, 'deleted');
        return $task->delete();
    }
}
```

- [ ] **Step 9: Update CommentController to dispatch event**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentAdded;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(int $taskId): AnonymousResourceCollection
    {
        $task = Task::findOrFail($taskId);
        $comments = $task->comments()->with('user:id,name,email')->latest()->get();

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);

        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated()['comment'],
        ]);

        $comment->load('user:id,name,email');

        CommentAdded::dispatch($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => new CommentResource($comment),
        ], 201);
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse
    {
        $comment = TaskComment::where('user_id', auth()->id())->findOrFail($commentId);
        $comment->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    public function destroy(int $commentId): JsonResponse
    {
        $comment = TaskComment::where('user_id', auth()->id())->findOrFail($commentId);
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }
}
```

- [ ] **Step 10: Setup queue configuration in .env**

```env
QUEUE_CONNECTION=database
```

- [ ] **Step 11: Create queue tables migration**

```bash
cd backend
php artisan queue:table
php artisan migrate
```

- [ ] **Step 12: Run queue worker**

```bash
php artisan queue:work
```

- [ ] **Step 13: Commit**

```bash
git add backend/app/Jobs/ backend/app/Mail/ backend/app/Events/ backend/app/Services/TaskService.php backend/app/Http/Controllers/Api/CommentController.php
git commit -m "feat: add background jobs and event notifications"
```

---

### Task 7: Frontend Setup - Next.js Project

**Files:**
- Create: `frontend/package.json`
- Create: `frontend/next.config.js`
- Create: `frontend/tsconfig.json`
- Create: `frontend/tailwind.config.ts`
- Create: `frontend/postcss.config.js`

**Interfaces:**
- Consumes: Next.js 14, React, Tailwind CSS
- Produces: Initialized Next.js project structure

- [ ] **Step 1: Create package.json**

```json
{
  "name": "task-management-frontend",
  "version": "1.0.0",
  "private": true,
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "next lint"
  },
  "dependencies": {
    "next": "14.2.5",
    "react": "^18.3.1",
    "react-dom": "^18.3.1",
    "axios": "^1.7.2",
    "js-cookie": "^3.0.5",
    "lucide-react": "^0.400.0",
    "clsx": "^2.1.1"
  },
  "devDependencies": {
    "@types/node": "^20.14.10",
    "@types/react": "^18.3.3",
    "@types/react-dom": "^18.3.0",
    "@types/js-cookie": "^3.0.6",
    "typescript": "^5.5.3",
    "tailwindcss": "^3.4.6",
    "postcss": "^8.4.39",
    "autoprefixer": "^10.4.19",
    "eslint": "^8.57.0",
    "eslint-config-next": "14.2.5"
  }
}
```

- [ ] **Step 2: Create next.config.js**

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/storage/**',
      },
    ],
  },
}

module.exports = nextConfig
```

- [ ] **Step 3: Create tsconfig.json**

```json
{
  "compilerOptions": {
    "lib": ["dom", "dom.iterable", "esnext"],
    "allowJs": true,
    "skipLibCheck": true,
    "strict": true,
    "noEmit": true,
    "esModuleInterop": true,
    "module": "esnext",
    "moduleResolution": "bundler",
    "resolveJsonModule": true,
    "isolatedModules": true,
    "jsx": "preserve",
    "incremental": true,
    "plugins": [
      {
        "name": "next"
      }
    ],
    "paths": {
      "@/*": ["./*"]
    }
  },
  "include": ["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"],
  "exclude": ["node_modules"]
}
```

- [ ] **Step 4: Create tailwind.config.ts**

```typescript
import type { Config } from 'tailwindcss'

const config: Config = {
  content: [
    './pages/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
      },
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },
    },
  },
  plugins: [],
}

export default config
```

- [ ] **Step 5: Create postcss.config.js**

```javascript
module.exports = {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

- [ ] **Step 6: Create app/globals.css**

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  :root {
    --background: 0 0% 100%;
    --foreground: 222.2 84% 4.9%;
    --card: 0 0% 100%;
    --card-foreground: 222.2 84% 4.9%;
    --primary: 221.2 83.2% 53.3%;
    --primary-foreground: 210 40% 98%;
    --secondary: 210 40% 96.1%;
    --secondary-foreground: 222.2 47.4% 11.2%;
    --muted: 210 40% 96.1%;
    --muted-foreground: 215.4 16.3% 46.9%;
    --accent: 210 40% 96.1%;
    --accent-foreground: 222.2 47.4% 11.2%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 210 40% 98%;
    --border: 214.3 31.8% 91.4%;
    --input: 214.3 31.8% 91.4%;
    --ring: 221.2 83.2% 53.3%;
    --radius: 0.5rem;
  }

  .dark {
    --background: 222.2 84% 4.9%;
    --foreground: 210 40% 98%;
    --card: 222.2 84% 4.9%;
    --card-foreground: 210 40% 98%;
    --primary: 217.2 91.2% 59.8%;
    --primary-foreground: 222.2 47.4% 11.2%;
    --secondary: 217.2 32.6% 17.5%;
    --secondary-foreground: 210 40% 98%;
    --muted: 217.2 32.6% 17.5%;
    --muted-foreground: 215 20.2% 65.1%;
    --accent: 217.2 32.6% 17.5%;
    --accent-foreground: 210 40% 98%;
    --destructive: 0 62.8% 30.6%;
    --destructive-foreground: 210 40% 98%;
    --border: 217.2 32.6% 17.5%;
    --input: 217.2 32.6% 17.5%;
    --ring: 224.3 76.3% 48%;
  }
}

@layer base {
  * {
    @apply border-border;
  }
  body {
    @apply bg-background text-foreground;
  }
}
```

- [ ] **Step 7: Create app/layout.tsx**

```typescript
import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'Task Management Platform',
  description: 'Full-stack task management system',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body className="min-h-screen bg-gray-50">
        {children}
      </body>
    </html>
  )
}
```

- [ ] **Step 8: Install dependencies**

```bash
cd frontend
npm install
```

- [ ] **Step 9: Commit**

```bash
git add frontend/
git commit -m "feat: setup Next.js 14 project with TypeScript and Tailwind CSS"
```

---

### Task 8: Frontend - API Client & Auth

**Files:**
- Create: `frontend/lib/api.ts`
- Create: `frontend/lib/auth.ts`
- Create: `frontend/lib/types.ts`
- Create: `frontend/components/ui/Button.tsx`
- Create: `frontend/components/ui/Input.tsx`
- Create: `frontend/components/ui/Card.tsx`

**Interfaces:**
- Consumes: Backend API endpoints
- Produces: Typed API client, auth hooks, reusable UI components

- [ ] **Step 1: Create lib/types.ts**

```typescript
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'manager' | 'user'
  created_at: string
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  assigned_user: {
    id: number
    name: string
    email: string
  } | null
  creator: {
    id: number
    name: string
    email: string
  }
  due_date: string | null
  comments_count?: number
  attachments_count?: number
  created_at: string
  updated_at: string
}

export interface Comment {
  id: number
  task_id: number
  user: {
    id: number
    name: string
    email: string
  }
  comment: string
  created_at: string
  updated_at: string
}

export interface Attachment {
  id: number
  task_id: number
  file_name: string
  file_path: string
  file_size: number
  mime_type: string
  uploaded_by: number
  uploaded_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export interface ApiResponse<T> {
  success: boolean
  message?: string
  data: T
}

export interface TaskFilters {
  page?: number
  per_page?: number
  status?: string
  priority?: string
  assigned_to?: number
  created_by?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  search?: string
}
```

- [ ] **Step 2: Create lib/api.ts**

```typescript
import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios'
import Cookies from 'js-cookie'

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = Cookies.get('token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      Cookies.remove('token')
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export interface LoginData {
  email: string
  password: string
}

export interface LoginResponse {
  success: boolean
  data: {
    access_token: string
    token_type: string
    expires_in: number
  }
}

export const authApi = {
  login: (data: LoginData) => api.post<LoginResponse>('/auth/login', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
}

export interface TaskData {
  title: string
  description?: string
  status?: string
  priority?: string
  assigned_user_id?: number
  due_date?: string
}

export const taskApi = {
  list: (params?: Record<string, any>) => api.get('/tasks', { params }),
  show: (id: number) => api.get(`/tasks/${id}`),
  create: (data: TaskData) => api.post('/tasks', data),
  update: (id: number, data: Partial<TaskData>) => api.put(`/tasks/${id}`, data),
  delete: (id: number) => api.delete(`/tasks/${id}`),
}

export const commentApi = {
  list: (taskId: number) => api.get(`/tasks/${taskId}/comments`),
  create: (taskId: number, comment: string) => api.post(`/tasks/${taskId}/comments`, { comment }),
  update: (id: number, comment: string) => api.put(`/comments/${id}`, { comment }),
  delete: (id: number) => api.delete(`/comments/${id}`),
}

export const attachmentApi = {
  list: (taskId: number) => api.get(`/tasks/${taskId}/attachments`),
  upload: (taskId: number, file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post(`/tasks/${taskId}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  download: (id: number) => `${API_BASE_URL}/attachments/${id}/download`,
  delete: (id: number) => api.delete(`/attachments/${id}`),
}
```

- [ ] **Step 3: Create lib/auth.ts**

```typescript
import Cookies from 'js-cookie'
import { authApi } from './api'
import type { User, LoginData, LoginResponse } from './types'

const TOKEN_KEY = 'token'

export const auth = {
  getToken: (): string | undefined => {
    return Cookies.get(TOKEN_KEY)
  },

  setToken: (token: string): void => {
    Cookies.set(TOKEN_KEY, token, { expires: 7 })
  },

  removeToken: (): void => {
    Cookies.remove(TOKEN_KEY)
  },

  isAuthenticated: (): boolean => {
    return !!Cookies.get(TOKEN_KEY)
  },

  login: async (data: LoginData): Promise<LoginResponse['data']> => {
    const response = await authApi.login(data)
    auth.setToken(response.data.data.access_token)
    return response.data.data
  },

  logout: async (): Promise<void> => {
    try {
      await authApi.logout()
    } finally {
      auth.removeToken()
    }
  },

  getCurrentUser: async (): Promise<User> => {
    const response = await authApi.me()
    return response.data.data
  },
}
```

- [ ] **Step 4: Create Button component**

```typescript
import { ButtonHTMLAttributes, forwardRef } from 'react'
import { clsx } from 'clsx'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'default', size = 'default', ...props }, ref) => {
    const variants = {
      default: 'bg-primary text-primary-foreground hover:bg-primary/90',
      destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
      outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
      secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
      ghost: 'hover:bg-accent hover:text-accent-foreground',
      link: 'text-primary underline-offset-4 hover:underline',
    }

    const sizes = {
      default: 'h-10 px-4 py-2',
      sm: 'h-9 rounded-md px-3',
      lg: 'h-11 rounded-md px-8',
      icon: 'h-10 w-10',
    }

    return (
      <button
        className={clsx(
          'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
          variants[variant],
          sizes[size],
          className
        )}
        ref={ref}
        {...props}
      />
    )
  }
)

Button.displayName = 'Button'

export { Button }
```

- [ ] **Step 5: Create Input component**

```typescript
import { InputHTMLAttributes, forwardRef } from 'react'
import { clsx } from 'clsx'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: string
}

const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, type, error, ...props }, ref) => {
    return (
      <div className="w-full">
        <input
          type={type}
          className={clsx(
            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-destructive',
            className
          )}
          ref={ref}
          {...props}
        />
        {error && <p className="mt-1 text-sm text-destructive">{error}</p>}
      </div>
    )
  }
)

Input.displayName = 'Input'

export { Input }
```

- [ ] **Step 6: Create Card component**

```typescript
import { HTMLAttributes, forwardRef } from 'react'
import { clsx } from 'clsx'

const Card = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <div
      ref={ref}
      className={clsx(
        'rounded-lg border bg-card text-card-foreground shadow-sm',
        className
      )}
      {...props}
    />
  )
)
Card.displayName = 'Card'

const CardHeader = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <div ref={ref} className={clsx('flex flex-col space-y-1.5 p-6', className)} {...props} />
  )
)
CardHeader.displayName = 'CardHeader'

const CardTitle = forwardRef<HTMLParagraphElement, HTMLAttributes<HTMLHeadingElement>>(
  ({ className, ...props }, ref) => (
    <h3
      ref={ref}
      className={clsx('text-2xl font-semibold leading-none tracking-tight', className)}
      {...props}
    />
  )
)
CardTitle.displayName = 'CardTitle'

const CardDescription = forwardRef<HTMLParagraphElement, HTMLAttributes<HTMLParagraphElement>>(
  ({ className, ...props }, ref) => (
    <p ref={ref} className={clsx('text-sm text-muted-foreground', className)} {...props} />
  )
)
CardDescription.displayName = 'CardDescription'

const CardContent = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <div ref={ref} className={clsx('p-6 pt-0', className)} {...props} />
  )
)
CardContent.displayName = 'CardContent'

const CardFooter = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <div ref={ref} className={clsx('flex items-center p-6 pt-0', className)} {...props} />
  )
)
CardFooter.displayName = 'CardFooter'

export { Card, CardHeader, CardFooter, CardTitle, CardDescription, CardContent }
```

- [ ] **Step 7: Commit**

```bash
git add frontend/lib/ frontend/components/ui/
git commit -m "feat: add API client, auth utilities, and UI components"
```

---

### Task 9: Frontend - Login Page

**Files:**
- Create: `frontend/app/(auth)/layout.tsx`
- Create: `frontend/app/(auth)/login/page.tsx`

**Interfaces:**
- Consumes: Auth API
- Produces: Login page with form validation

- [ ] **Step 1: Create auth layout**

```typescript
export default function AuthLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="w-full max-w-md px-4">
        {children}
      </div>
    </div>
  )
}
```

- [ ] **Step 2: Create login page**

```typescript
'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { auth } from '@/lib/auth'
import { AlertCircle } from 'lucide-react'

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      await auth.login({ email, password })
      router.push('/dashboard')
    } catch (err: any) {
      setError(err.response?.data?.message || 'Login failed. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl text-center">Task Management</CardTitle>
        <CardDescription className="text-center">
          Enter your credentials to access your account
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {error && (
            <div className="flex items-center gap-2 p-3 text-sm text-destructive bg-destructive/10 rounded-md">
              <AlertCircle className="h-4 w-4" />
              {error}
            </div>
          )}
          
          <div className="space-y-2">
            <label htmlFor="email" className="text-sm font-medium">
              Email
            </label>
            <Input
              id="email"
              type="email"
              placeholder="admin@example.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>

          <div className="space-y-2">
            <label htmlFor="password" className="text-sm font-medium">
              Password
            </label>
            <Input
              id="password"
              type="password"
              placeholder="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? 'Signing in...' : 'Sign in'}
          </Button>
        </form>

        <div className="mt-4 p-3 bg-muted rounded-md text-xs">
          <p className="font-medium mb-1">Test Credentials:</p>
          <p>Admin: admin@example.com / password</p>
          <p>Manager: manager@example.com / password</p>
          <p>User: john@example.com / password</p>
        </div>
      </CardContent>
    </Card>
  )
}
```

- [ ] **Step 3: Add middleware for auth protection**

```typescript
// frontend/middleware.ts
import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'
import { auth } from '@/lib/auth'

export function middleware(request: NextRequest) {
  const token = auth.getToken()
  const isAuthPage = request.nextUrl.pathname.startsWith('/login')
  const isApiRoute = request.nextUrl.pathname.startsWith('/api')

  if (!token && !isAuthPage && !isApiRoute) {
    return NextResponse.redirect(new URL('/login', request.url))
  }

  if (token && isAuthPage) {
    return NextResponse.redirect(new URL('/dashboard', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
}
```

- [ ] **Step 4: Commit**

```bash
git add frontend/app/\(auth\)/ frontend/middleware.ts
git commit -m "feat: add login page with form validation"
```

---

### Task 10: Frontend - Dashboard & Task List

**Files:**
- Create: `frontend/app/(dashboard)/layout.tsx`
- Create: `frontend/app/(dashboard)/page.tsx` (dashboard)
- Create: `frontend/components/layout/Sidebar.tsx`
- Create: `frontend/components/layout/Header.tsx`
- Create: `frontend/components/tasks/TaskCard.tsx`
- Create: `frontend/components/tasks/TaskList.tsx`
- Create: `frontend/components/tasks/TaskFilter.tsx`

**Interfaces:**
- Consumes: Task API, auth
- Produces: Dashboard with task list and filtering

- [ ] **Step 1: Create dashboard layout with sidebar**

```typescript
import { Header } from '@/components/layout/Header'
import { Sidebar } from '@/components/layout/Sidebar'

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-6">
          {children}
        </main>
      </div>
    </div>
  )
}
```

- [ ] **Step 2: Create Header component**

```typescript
'use client'

import { useRouter } from 'next/navigation'
import { auth } from '@/lib/auth'
import { Button } from '@/components/ui/Button'
import { LogOut, User } from 'lucide-react'

export function Header() {
  const router = useRouter()

  const handleLogout = async () => {
    await auth.logout()
    router.push('/login')
  }

  return (
    <header className="bg-white border-b sticky top-0 z-10">
      <div className="flex items-center justify-between px-6 py-4">
        <h1 className="text-xl font-semibold">Task Management</h1>
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <User className="h-4 w-4" />
            <span>Demo User</span>
          </div>
          <Button variant="outline" size="sm" onClick={handleLogout}>
            <LogOut className="h-4 w-4 mr-2" />
            Logout
          </Button>
        </div>
      </div>
    </header>
  )
}
```

- [ ] **Step 3: Create Sidebar component**

```typescript
'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { clsx } from 'clsx'
import { LayoutDashboard, ListTodo, Users, Settings } from 'lucide-react'

const navItems = [
  { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/dashboard/tasks', label: 'Tasks', icon: ListTodo },
]

export function Sidebar() {
  const pathname = usePathname()

  return (
    <aside className="w-64 bg-white border-r min-h-[calc(100vh-73px)] p-4">
      <nav className="space-y-1">
        {navItems.map((item) => {
          const Icon = item.icon
          const isActive = pathname === item.href || pathname.startsWith(item.href + '/')
          
          return (
            <Link
              key={item.href}
              href={item.href}
              className={clsx(
                'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors',
                isActive
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              )}
            >
              <Icon className="h-4 w-4" />
              {item.label}
            </Link>
          )
        })}
      </nav>
    </aside>
  )
}
```

- [ ] **Step 4: Create TaskCard component**

```typescript
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { Task } from '@/lib/types'
import { Calendar, User, MessageSquare, Paperclip } from 'lucide-react'

interface TaskCardProps {
  task: Task
  onClick?: () => void
}

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800',
  in_progress: 'bg-blue-100 text-blue-800',
  completed: 'bg-green-100 text-green-800',
  cancelled: 'bg-gray-100 text-gray-800',
}

const priorityColors = {
  low: 'border-l-gray-400',
  medium: 'border-l-blue-500',
  high: 'border-l-orange-500',
  urgent: 'border-l-red-500',
}

export function TaskCard({ task, onClick }: TaskCardProps) {
  return (
    <Card 
      className={`border-l-4 hover:shadow-md transition-shadow cursor-pointer ${priorityColors[task.priority]}`}
      onClick={onClick}
    >
      <CardHeader className="pb-2">
        <div className="flex items-start justify-between gap-2">
          <h3 className="font-semibold line-clamp-1">{task.title}</h3>
          <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusColors[task.status]}`}>
            {task.status.replace('_', ' ')}
          </span>
        </div>
      </CardHeader>
      <CardContent className="space-y-3">
        {task.description && (
          <p className="text-sm text-muted-foreground line-clamp-2">
            {task.description}
          </p>
        )}
        
        <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
          {task.assigned_user && (
            <div className="flex items-center gap-1">
              <User className="h-3 w-3" />
              {task.assigned_user.name}
            </div>
          )}
          {task.due_date && (
            <div className="flex items-center gap-1">
              <Calendar className="h-3 w-3" />
              {new Date(task.due_date).toLocaleDateString()}
            </div>
          )}
        </div>

        <div className="flex items-center justify-between pt-2 border-t">
          <span className={`text-xs px-2 py-1 rounded ${
            task.priority === 'urgent' ? 'bg-red-100 text-red-700' :
            task.priority === 'high' ? 'bg-orange-100 text-orange-700' :
            task.priority === 'medium' ? 'bg-blue-100 text-blue-700' :
            'bg-gray-100 text-gray-700'
          }`}>
            {task.priority}
          </span>
          
          <div className="flex items-center gap-3 text-xs text-muted-foreground">
            <span className="flex items-center gap-1">
              <MessageSquare className="h-3 w-3" />
              {task.comments_count || 0}
            </span>
            <span className="flex items-center gap-1">
              <Paperclip className="h-3 w-3" />
              {task.attachments_count || 0}
            </span>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
```

- [ ] **Step 5: Create TaskFilter component**

```typescript
'use client'

import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { Search, Filter, X } from 'lucide-react'
import { TaskFilters } from '@/lib/types'

interface TaskFilterProps {
  filters: TaskFilters
  onFilterChange: (filters: TaskFilters) => void
}

export function TaskFilter({ filters, onFilterChange }: TaskFilterProps) {
  const handleChange = (key: keyof TaskFilters, value: any) => {
    onFilterChange({ ...filters, [key]: value || undefined })
  }

  const clearFilters = () => {
    onFilterChange({})
  }

  const hasFilters = Object.values(filters).some(v => v)

  return (
    <div className="bg-white p-4 rounded-lg border space-y-4">
      <div className="flex items-center gap-4">
        <div className="flex-1">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Search tasks..."
              className="pl-10"
              value={filters.search || ''}
              onChange={(e) => handleChange('search', e.target.value)}
            />
          </div>
        </div>
        
        <select
          className="h-10 px-3 rounded-md border border-input bg-background text-sm"
          value={filters.status || ''}
          onChange={(e) => handleChange('status', e.target.value)}
        >
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>

        <select
          className="h-10 px-3 rounded-md border border-input bg-background text-sm"
          value={filters.priority || ''}
          onChange={(e) => handleChange('priority', e.target.value)}
        >
          <option value="">All Priority</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>

        {hasFilters && (
          <Button variant="ghost" size="sm" onClick={clearFilters}>
            <X className="h-4 w-4 mr-1" />
            Clear
          </Button>
        )}
      </div>
    </div>
  )
}
```

- [ ] **Step 6: Create TaskList component**

```typescript
'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { TaskCard } from './TaskCard'
import { TaskFilter } from './TaskFilter'
import { taskApi } from '@/lib/api'
import { Task, TaskFilters, PaginatedResponse } from '@/lib/types'
import { Button } from '@/components/ui/Button'
import { Plus, ChevronLeft, ChevronRight } from 'lucide-react'

export function TaskList() {
  const router = useRouter()
  const [tasks, setTasks] = useState<Task[]>([])
  const [loading, setLoading] = useState(true)
  const [filters, setFilters] = useState<TaskFilters>({
    page: 1,
    per_page: 12,
  })
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
  })

  useEffect(() => {
    fetchTasks()
  }, [filters])

  const fetchTasks = async () => {
    setLoading(true)
    try {
      const response = await taskApi.list(filters)
      const data = response.data as PaginatedResponse<Task>
      setTasks(data.data)
      setPagination({
        current_page: data.meta.current_page,
        last_page: data.meta.last_page,
        total: data.meta.total,
      })
    } catch (error) {
      console.error('Failed to fetch tasks:', error)
    } finally {
      setLoading(false)
    }
  }

  const handlePageChange = (page: number) => {
    setFilters({ ...filters, page })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold">Tasks</h2>
        <Button onClick={() => router.push('/dashboard/tasks/new')}>
          <Plus className="h-4 w-4 mr-2" />
          New Task
        </Button>
      </div>

      <TaskFilter filters={filters} onFilterChange={setFilters} />

      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="h-48 bg-gray-200 animate-pulse rounded-lg" />
          ))}
        </div>
      ) : tasks.length === 0 ? (
        <div className="text-center py-12 text-muted-foreground">
          No tasks found. Create your first task!
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {tasks.map((task) => (
              <TaskCard
                key={task.id}
                task={task}
                onClick={() => router.push(`/dashboard/tasks/${task.id}`)}
              />
            ))}
          </div>

          {pagination.last_page > 1 && (
            <div className="flex items-center justify-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => handlePageChange(pagination.current_page - 1)}
                disabled={pagination.current_page === 1}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <span className="text-sm">
                Page {pagination.current_page} of {pagination.last_page}
              </span>
              <Button
                variant="outline"
                size="sm"
                onClick={() => handlePageChange(pagination.current_page + 1)}
                disabled={pagination.current_page === pagination.last_page}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
```

- [ ] **Step 7: Create dashboard page**

```typescript
import { TaskList } from '@/components/tasks/TaskList'

export default function DashboardPage() {
  return <TaskList />
}
```

- [ ] **Step 8: Commit**

```bash
git add frontend/app/\(dashboard\)/ frontend/components/layout/ frontend/components/tasks/
git commit -m "feat: add dashboard with task list, filtering, and pagination"
```

---

### Task 11: Frontend - Task Detail & Create

**Files:**
- Create: `frontend/app/(dashboard)/tasks/[id]/page.tsx`
- Create: `frontend/app/(dashboard)/tasks/new/page.tsx`
- Create: `frontend/components/tasks/TaskForm.tsx`
- Create: `frontend/components/comments/CommentList.tsx`
- Create: `frontend/components/comments/CommentForm.tsx`

**Interfaces:**
- Consumes: Task API, Comment API, Attachment API
- Produces: Task detail page, create task form, comments

- [ ] **Step 1: Create TaskForm component**

```typescript
'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { taskApi } from '@/lib/api'
import { Task, TaskData } from '@/lib/types'

interface TaskFormProps {
  task?: Task
  mode: 'create' | 'edit'
}

export function TaskForm({ task, mode }: TaskFormProps) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [formData, setFormData] = useState<TaskData>({
    title: task?.title || '',
    description: task?.description || '',
    status: task?.status || 'pending',
    priority: task?.priority || 'medium',
    due_date: task?.due_date || '',
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      if (mode === 'create') {
        await taskApi.create(formData)
      } else if (task) {
        await taskApi.update(task.id, formData)
      }
      router.push('/dashboard')
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to save task')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{mode === 'create' ? 'Create New Task' : 'Edit Task'}</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {error && (
            <div className="p-3 text-sm text-destructive bg-destructive/10 rounded-md">
              {error}
            </div>
          )}

          <div className="space-y-2">
            <label className="text-sm font-medium">Title</label>
            <Input
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              placeholder="Task title"
              required
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Description</label>
            <textarea
              className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              rows={4}
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Task description"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <label className="text-sm font-medium">Status</label>
              <select
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              >
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Priority</label>
              <select
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={formData.priority}
                onChange={(e) => setFormData({ ...formData, priority: e.target.value })}
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Due Date</label>
            <Input
              type="date"
              value={formData.due_date}
              onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
            />
          </div>

          <div className="flex gap-2 pt-4">
            <Button type="submit" disabled={loading}>
              {loading ? 'Saving...' : mode === 'create' ? 'Create Task' : 'Update Task'}
            </Button>
            <Button type="button" variant="outline" onClick={() => router.back()}>
              Cancel
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  )
}
```

- [ ] **Step 2: Create CommentForm component**

```typescript
'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { commentApi } from '@/lib/api'
import { Send } from 'lucide-react'

interface CommentFormProps {
  taskId: number
  onCommentAdded: () => void
}

export function CommentForm({ taskId, onCommentAdded }: CommentFormProps) {
  const [comment, setComment] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!comment.trim()) return

    setLoading(true)
    try {
      await commentApi.create(taskId, comment)
      setComment('')
      onCommentAdded()
    } catch (error) {
      console.error('Failed to add comment:', error)
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex gap-2">
      <Input
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        placeholder="Add a comment..."
        className="flex-1"
      />
      <Button type="submit" size="sm" disabled={loading || !comment.trim()}>
        <Send className="h-4 w-4" />
      </Button>
    </form>
  )
}
```

- [ ] **Step 3: Create CommentList component**

```typescript
'use client'

import { useState, useEffect } from 'react'
import { commentApi } from '@/lib/api'
import { Comment } from '@/lib/types'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { MessageSquare } from 'lucide-react'

interface CommentListProps {
  taskId: number
  refreshKey: number
}

export function CommentList({ taskId, refreshKey }: CommentListProps) {
  const [comments, setComments] = useState<Comment[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchComments()
  }, [taskId, refreshKey])

  const fetchComments = async () => {
    try {
      const response = await commentApi.list(taskId)
      setComments(response.data.data)
    } catch (error) {
      console.error('Failed to fetch comments:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return <div className="text-sm text-muted-foreground">Loading comments...</div>
  }

  if (comments.length === 0) {
    return (
      <div className="text-sm text-muted-foreground flex items-center gap-2">
        <MessageSquare className="h-4 w-4" />
        No comments yet
      </div>
    )
  }

  return (
    <div className="space-y-3">
      {comments.map((comment) => (
        <div key={comment.id} className="bg-muted/50 rounded-lg p-3">
          <div className="flex items-center justify-between mb-1">
            <span className="font-medium text-sm">{comment.user.name}</span>
            <span className="text-xs text-muted-foreground">
              {new Date(comment.created_at).toLocaleString()}
            </span>
          </div>
          <p className="text-sm">{comment.comment}</p>
        </div>
      ))}
    </div>
  )
}
```

- [ ] **Step 4: Create Task Detail page**

```typescript
'use client'

import { useState, useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import { taskApi } from '@/lib/api'
import { Task } from '@/lib/types'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { CommentList } from '@/components/comments/CommentList'
import { CommentForm } from '@/components/comments/CommentForm'
import { ArrowLeft, Calendar, User, Edit, Trash2, Paperclip } from 'lucide-react'

export default function TaskDetailPage() {
  const params = useParams()
  const router = useRouter()
  const taskId = parseInt(params.id as string)
  
  const [task, setTask] = useState<Task | null>(null)
  const [loading, setLoading] = useState(true)
  const [commentRefresh, setCommentRefresh] = useState(0)

  useEffect(() => {
    fetchTask()
  }, [taskId])

  const fetchTask = async () => {
    try {
      const response = await taskApi.show(taskId)
      setTask(response.data.data)
    } catch (error) {
      console.error('Failed to fetch task:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async () => {
    if (!confirm('Are you sure you want to delete this task?')) return
    
    try {
      await taskApi.delete(taskId)
      router.push('/dashboard')
    } catch (error) {
      console.error('Failed to delete task:', error)
    }
  }

  const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }

  if (loading) {
    return <div className="animate-pulse space-y-4">
      <div className="h-8 bg-gray-200 rounded w-1/3"></div>
      <div className="h-64 bg-gray-200 rounded"></div>
    </div>
  }

  if (!task) {
    return <div>Task not found</div>
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between">
            <div className="space-y-2">
              <CardTitle className="text-2xl">{task.title}</CardTitle>
              <div className="flex items-center gap-2">
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[task.status]}`}>
                  {task.status.replace('_', ' ')}
                </span>
                <span className={`px-3 py-1 rounded-full text-sm ${
                  task.priority === 'urgent' ? 'bg-red-100 text-red-700' :
                  task.priority === 'high' ? 'bg-orange-100 text-orange-700' :
                  task.priority === 'medium' ? 'bg-blue-100 text-blue-700' :
                  'bg-gray-100 text-gray-700'
                }`}>
                  {task.priority} priority
                </span>
              </div>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => router.push(`/dashboard/tasks/${taskId}/edit`)}>
                <Edit className="h-4 w-4 mr-2" />
                Edit
              </Button>
              <Button variant="destructive" onClick={handleDelete}>
                <Trash2 className="h-4 w-4 mr-2" />
                Delete
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent className="space-y-6">
          {task.description && (
            <div>
              <h3 className="font-medium mb-2">Description</h3>
              <p className="text-muted-foreground">{task.description}</p>
            </div>
          )}

          <div className="grid grid-cols-2 gap-4">
            <div className="flex items-center gap-2 text-sm">
              <User className="h-4 w-4 text-muted-foreground" />
              <span>Assigned to: {task.assigned_user?.name || 'Unassigned'}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <User className="h-4 w-4 text-muted-foreground" />
              <span>Created by: {task.creator?.name}</span>
            </div>
            {task.due_date && (
              <div className="flex items-center gap-2 text-sm">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <span>Due: {new Date(task.due_date).toLocaleDateString()}</span>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Comments</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <CommentForm taskId={taskId} onCommentAdded={() => setCommentRefresh(c => c + 1)} />
          <CommentList taskId={taskId} refreshKey={commentRefresh} />
        </CardContent>
      </Card>
    </div>
  )
}
```

- [ ] **Step 5: Create New Task page**

```typescript
import { TaskForm } from '@/components/tasks/TaskForm'

export default function NewTaskPage() {
  return <TaskForm mode="create" />
}
```

- [ ] **Step 6: Create Edit Task page**

```typescript
'use client'

import { useState, useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import { taskApi } from '@/lib/api'
import { Task } from '@/lib/types'
import { TaskForm } from '@/components/tasks/TaskForm'

export default function EditTaskPage() {
  const params = useParams()
  const router = useRouter()
  const taskId = parseInt(params.id as string)
  
  const [task, setTask] = useState<Task | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchTask()
  }, [taskId])

  const fetchTask = async () => {
    try {
      const response = await taskApi.show(taskId)
      setTask(response.data.data)
    } catch (error) {
      console.error('Failed to fetch task:', error)
      router.push('/dashboard')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return <div>Loading...</div>
  }

  if (!task) {
    return null
  }

  return <TaskForm task={task} mode="edit" />
}
```

- [ ] **Step 7: Commit**

```bash
git add frontend/app/\(dashboard\)/tasks/ frontend/components/tasks/TaskForm.tsx frontend/components/comments/
git commit -m "feat: add task detail, create, and edit pages with comments"
```

---

### Task 12: Final Polish & README

**Files:**
- Create: `backend/README.md`
- Create: `frontend/README.md`
- Create: `README.md` (root)
- Create: `.env.example` files
- Modify: `.gitignore`

**Interfaces:**
- Consumes: Completed project
- Produces: Documentation and setup instructions

- [ ] **Step 1: Create root README.md**

```markdown
# Task Management Platform

A full-stack task management system built for technical assessment.

## Tech Stack

- **Backend:** Laravel 13, PHP 8.2+, MySQL 8.0, Redis
- **Frontend:** Next.js 14, TypeScript, Tailwind CSS
- **Auth:** JWT Authentication

## Project Structure

```
project-root/
├── backend/          # Laravel API
├── frontend/         # Next.js Frontend
├── docs/             # Documentation
└── README.md
```

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0
- Redis (optional, for queues)

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate keys
php artisan key:generate
php artisan jwt:secret

# Create database
mysql -u root -p
CREATE DATABASE task_management;

# Run migrations and seeders
php artisan migrate:fresh --seed

# Start server
php artisan serve
```

### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Copy environment file
cp .env.example .env.local

# Start development server
npm run dev
```

### Default Credentials

| Role    | Email                | Password |
|---------|----------------------|----------|
| Admin   | admin@example.com    | password |
| Manager | manager@example.com  | password |
| User    | john@example.com     | password |

## API Endpoints

### Authentication

- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

### Tasks

- `GET /api/tasks` - List tasks (with pagination & filters)
- `POST /api/tasks` - Create task
- `GET /api/tasks/{id}` - Get task detail
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task

### Comments

- `GET /api/tasks/{id}/comments` - List comments
- `POST /api/tasks/{id}/comments` - Add comment
- `PUT /api/comments/{id}` - Update comment
- `DELETE /api/comments/{id}` - Delete comment

### Attachments

- `GET /api/tasks/{id}/attachments` - List attachments
- `POST /api/tasks/{id}/attachments` - Upload file
- `GET /api/attachments/{id}/download` - Download file
- `DELETE /api/attachments/{id}` - Delete file

## Features

- [x] JWT Authentication
- [x] Task CRUD with pagination
- [x] Task filtering & search
- [x] Comments system
- [x] File uploads with thumbnails
- [x] Background job processing
- [x] Email notifications
- [x] Real-time events (Laravel Events)
- [x] Responsive UI

## License

MIT
```

- [ ] **Step 2: Create backend/README.md**

```markdown
# Backend - Task Management API

Laravel 13 REST API for Task Management Platform.

## Setup

```bash
# Install dependencies
composer install

# Copy .env
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Create database and run migrations
php artisan migrate:fresh --seed

# Start server
php artisan serve --port=8000
```

## Queue Workers

For background job processing (email notifications):

```bash
php artisan queue:work
```

## Storage

Create symbolic link for public storage:

```bash
php artisan storage:link
```

## Testing

```bash
php artisan test
```

## API Base URL

`http://localhost:8000/api`
```

- [ ] **Step 3: Create frontend/README.md**

```markdown
# Frontend - Task Management UI

Next.js 14 frontend for Task Management Platform.

## Setup

```bash
# Install dependencies
npm install

# Create .env.local
echo "NEXT_PUBLIC_API_URL=http://localhost:8000/api" > .env.local

# Start development server
npm run dev
```

## Build

```bash
npm run build
npm start
```

## Pages

- `/login` - Login page
- `/dashboard` - Task list dashboard
- `/dashboard/tasks` - All tasks
- `/dashboard/tasks/new` - Create task
- `/dashboard/tasks/{id}` - Task detail
- `/dashboard/tasks/{id}/edit` - Edit task
```

- [ ] **Step 4: Create .env.example files**

```env
# backend/.env.example
APP_NAME="Task Management"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

JWT_SECRET=
```

```env
# frontend/.env.example
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

- [ ] **Step 5: Update .gitignore**

```gitignore
# Laravel
/backend/vendor/
/backend/node_modules/
/backend/.env
/backend/.env.backup
/backend/storage/*.key
/backend/storage/logs/*
!/backend/storage/logs/.gitkeep

# Next.js
/frontend/node_modules/
/frontend/.next/
/frontend/out/
/frontend/.env.local
/frontend/.env*.local

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Logs
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Testing
/coverage/
/.phpunit.result.cache
```

- [ ] **Step 6: Final test run**

```bash
# Backend
cd backend
php artisan serve

# Frontend (new terminal)
cd frontend
npm run dev
```

- [ ] **Step 7: Final commit**

```bash
git add -A
git commit -m "feat: complete Task Management Platform with auth, CRUD, comments, file upload"
```

---

## Implementation Order Summary

1. **Task 1:** Database setup (migrations & seeders)
2. **Task 2:** JWT Authentication
3. **Task 3:** Task CRUD API
4. **Task 4:** Comment System
5. **Task 5:** File Upload System
6. **Task 6:** Background Jobs & Notifications
7. **Task 7:** Frontend Setup
8. **Task 8:** Frontend API Client & Auth
9. **Task 9:** Frontend Login Page
10. **Task 10:** Frontend Dashboard & Task List
11. **Task 11:** Frontend Task Detail & Create
12. **Task 12:** Documentation & Polish

---

## Spec Coverage Check

| Requirement | Status |
|-------------|--------|
| Users table with roles | ✅ Task 1 |
| Tasks table with all fields | ✅ Task 1 |
| Task_comments table | ✅ Task 1 |
| Attachments table | ✅ Task 1 |
| Database seeder (5 users, 15 tasks, 10 comments) | ✅ Task 1 |
| JWT Authentication | ✅ Task 2 |
| Task CRUD with pagination/filtering | ✅ Task 3 |
| Comment CRUD | ✅ Task 4 |
| File upload with thumbnails | ✅ Task 5 |
| Background jobs | ✅ Task 6 |
| Email notifications | ✅ Task 6 |
| Next.js frontend | ✅ Tasks 7-11 |
| Responsive design | ✅ Tasks 7-11 |
| README documentation | ✅ Task 12 |

---

*Plan created: 2026-08-13*
*Project: Task Management Platform Assessment*
