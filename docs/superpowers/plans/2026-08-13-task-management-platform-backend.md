# Task Management Platform - Backend Implementation Plan

> **Phase 1: Backend Only (Laravel Monolith)**

**Goal:** Membangun REST API dengan Laravel 13 untuk Task Management Platform

**Architecture:** Single Laravel application dengan modular structure. Fitur: Auth (JWT), Tasks CRUD, Comments, File Upload, Background Jobs, Events/Notifications.

**Tech Stack:**
- Framework: Laravel 13 (PHP 8.2+)
- Database: MySQL 8.0
- Auth: JWT (tymon/jwt-auth)
- Queue: Database driver
- File Storage: Local disk with public visibility

## Global Constraints

- PHP Version: 8.2+
- Laravel Version: 13.x
- Database: MySQL 8.0
- Auth: JWT dengan tymon/jwt-auth
- File Upload Max: 50MB
- Commit Message: **TIDAK ADA co-author** - format: `git commit -m "description"` tanpa `-m "..." Co-Authored-By: ...`

---

## IMPORTANT: Git Commit Policy

```
✅ BENAR:
git commit -m "feat: add user authentication"
git commit -m "fix: resolve task filtering bug"

❌ SALAH (TIDAK PERNAH PAKAI):
git commit -m "description" -m "Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

## Laravel Best Practices Applied

1. **Use Artisan commands** - `php artisan make:model`, `php artisan make:migration`, etc.
2. **Use `constrained()`** for foreign keys
3. **Add indexes** in migrations for WHERE/ORDER BY columns
4. **Use `$fillable`** for mass assignment protection
5. **Use implicit route model binding** - `public function show(Task $task)`
6. **Use `Route::apiResource()`** for RESTful endpoints
7. **Keep controllers thin** - extract to Service classes
8. **Use Form Request classes** for validation
9. **Use `validated()`** instead of `$request->all()`
10. **Use dependency injection** in constructor
11. **Use local scopes** for reusable queries
12. **Use `latest()`** for default sorting

---

## Task 1: Project Setup & JWT Authentication

**Files to create/modify:**

- [ ] **Step 1: Install JWT auth package**

```bash
cd backend
composer require tymon/jwt-auth:^2.0
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret --force
```

- [ ] **Step 2: Generate auth scaffold**

```bash
php artisan make:controller AuthController
php artisan make:middleware JwtMiddleware
php artisan make:request LoginRequest
php artisan make:provider AuthServiceProvider
```

- [ ] **Step 3: Update User model** (`app/Models/User.php`)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /** @return array<string, mixed> */
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

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
```

- [ ] **Step 4: Create LoginRequest** (`app/Http/Requests/LoginRequest.php`)

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
```

- [ ] **Step 5: Create JwtMiddleware** (`app/Http/Middleware/JwtMiddleware.php`)

```php
<?php

declare(strict_types=1);

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

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], Response::HTTP_UNAUTHORIZED);
            }
        } catch (TokenExpiredException) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
            ], Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
            ], Response::HTTP_UNAUTHORIZED);
        } catch (JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
```

- [ ] **Step 6: Create AuthController** (`app/Http/Controllers/AuthController.php`)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
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
            'data' => auth()->user(),
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

- [ ] **Step 7: Register middleware in bootstrap/app.php**

```php
<?php

use App\Http\Middleware\JwtMiddleware;
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
            'jwt.auth' => JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

- [ ] **Step 8: Create API routes** (`routes/api.php`)

```php
<?php

use App\Http\Controllers\AuthController;
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

- [ ] **Step 9: Update config/auth.php**

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

- [ ] **Step 10: Run Pint to format code**

```bash
cd backend
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 11: Commit**

```bash
git add -A
git commit -m "feat: add JWT authentication with login, logout, me, and refresh endpoints"
```

---

## Task 2: Database Migrations & Models

**Files to create:**

- [ ] **Step 1: Create tasks table migration**

```bash
php artisan make:migration create_tasks_table
```

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

- [ ] **Step 2: Create task_comments table migration**

```bash
php artisan make:migration create_task_comments_table
```

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

- [ ] **Step 3: Create attachments table migration**

```bash
php artisan make:migration create_attachments_table
```

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

- [ ] **Step 4: Create Task model** (`app/Models/Task.php`)

```bash
php artisan make:model Task
```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['title', 'description', 'status', 'priority', 'assigned_user_id', 'created_by', 'due_date'])]
class Task extends Model
{
    use HasFactory;

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

    /** @param Builder<Task> $query */
    #[Scope]
    protected function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', $filters['status']);
            $query->whereIn('status', $statuses);
        }

        if (! empty($filters['priority'])) {
            $priorities = is_array($filters['priority']) ? $filters['priority'] : explode(',', $filters['priority']);
            $query->whereIn('priority', $priorities);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_user_id', $filters['assigned_to']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters): void {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query;
    }
}
```

- [ ] **Step 5: Create TaskComment model** (`app/Models/TaskComment.php`)

```bash
php artisan make:model TaskComment
```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'user_id', 'comment'])]
class TaskComment extends Model
{
    use HasFactory;

    protected $table = 'task_comments';

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

- [ ] **Step 6: Create Attachment model** (`app/Models/Attachment.php`)

```bash
php artisan make:model Attachment
```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'file_name', 'file_path', 'file_size', 'mime_type', 'uploaded_by', 'uploaded_at'])]
class Attachment extends Model
{
    use HasFactory;

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

- [ ] **Step 7: Run migrations**

```bash
php artisan migrate
```

- [ ] **Step 8: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add tasks, task_comments, and attachments tables with models"
```

---

## Task 3: Database Seeders

**Files to create:**

- [ ] **Step 1: Create UserSeeder** (`database/seeders/UserSeeder.php`)

```bash
php artisan make:seeder UserSeeder
```

```php
<?php

declare(strict_types=1);

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

- [ ] **Step 2: Create TaskSeeder** (`database/seeders/TaskSeeder.php`)

```bash
php artisan make:seeder TaskSeeder
```

```php
<?php

declare(strict_types=1);

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
            ['title' => 'Setup real-time notifications', 'description' => 'Events for task updates', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Write API documentation', 'description' => 'Swagger/OpenAPI docs', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Frontend dashboard', 'description' => 'Main dashboard UI', 'status' => 'pending', 'priority' => 'high'],
            ['title' => 'Task list view', 'description' => 'Paginated task listing with filters', 'status' => 'pending', 'priority' => 'high'],
            ['title' => 'Task detail page', 'description' => 'Single task view with comments', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'File upload UI', 'description' => 'Drag-drop upload interface', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'User management', 'description' => 'Admin user management panel', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Performance optimization', 'description' => 'Caching and query optimization', 'status' => 'pending', 'priority' => 'low'],
            ['title' => 'Testing', 'description' => 'Unit and integration tests', 'status' => 'pending', 'priority' => 'medium'],
        ];

        foreach ($tasks as $task) {
            Task::create([
                'title' => $task['title'],
                'description' => $task['description'],
                'status' => $task['status'],
                'priority' => $task['priority'],
                'assigned_user_id' => $users->random()->id,
                'created_by' => $users->where('role', '!=', 'user')->random()->id,
                'due_date' => now()->addDays(random_int(1, 30)),
            ]);
        }
    }
}
```

- [ ] **Step 3: Create CommentSeeder** (`database/seeders/CommentSeeder.php`)

```bash
php artisan make:seeder CommentSeeder
```

```php
<?php

declare(strict_types=1);

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

        foreach ($comments as $commentText) {
            TaskComment::create([
                'task_id' => $tasks->random()->id,
                'user_id' => $users->random()->id,
                'comment' => $commentText,
            ]);
        }
    }
}
```

- [ ] **Step 4: Update DatabaseSeeder** (`database/seeders/DatabaseSeeder.php`)

```php
<?php

declare(strict_types=1);

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

- [ ] **Step 5: Run seeders**

```bash
php artisan db:seed
```

- [ ] **Step 6: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add database seeders (5 users, 15 tasks, 10 comments)"
```

---

## Task 4: Task CRUD API

**Files to create:**

- [ ] **Step 1: Create TaskService** (`app/Services/TaskService.php`)

```bash
php artisan make:class Services/TaskService
```

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TaskUpdated;
use App\Jobs\SendTaskAssignmentEmail;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function getTasks(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
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

        if (! empty($data['assigned_user_id'])) {
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
        return Task::with([
            'assignedUser:id,name,email',
            'creator:id,name,email',
            'comments.user:id,name,email',
            'attachments',
        ])->findOrFail($id);
    }

    public function updateTask(Task $task, array $data): Task
    {
        $wasAssignedTo = $task->assigned_user_id;

        $task->update($data);
        $task = $task->fresh(['assignedUser:id,name,email', 'creator:id,name,email']);

        if (! empty($data['assigned_user_id']) && (int) $data['assigned_user_id'] !== (int) $wasAssignedTo) {
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

- [ ] **Step 2: Create Form Requests**

```bash
php artisan make:request StoreTaskRequest
php artisan make:request UpdateTaskRequest
```

`app/Http/Requests/StoreTaskRequest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
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

`app/Http/Requests/UpdateTaskRequest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
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

- [ ] **Step 3: Create TaskController** (`app/Http/Controllers/Api/TaskController.php`)

```bash
php artisan make:controller Api/TaskController
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->getTasks($request->all());

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'from' => $tasks->firstItem(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'to' => $tasks->lastItem(),
                'total' => $tasks->total(),
            ],
            'links' => [
                'first' => $tasks->url(1),
                'last' => $tasks->url($tasks->lastPage()),
                'prev' => $tasks->previousPageUrl(),
                'next' => $tasks->nextPageUrl(),
            ],
        ]);
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

- [ ] **Step 4: Create TaskResource** (`app/Http/Resources/TaskResource.php`)

```bash
php artisan make:resource TaskResource
```

```php
<?php

declare(strict_types=1);

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
            'comments' => $this->whenLoaded('comments', fn() => CommentResource::collection($this->comments)),
            'attachments' => $this->whenLoaded('attachments'),
            'due_date' => $this->due_date?->toDateString(),
            'comments_count' => $this->when(
                $this->comments_count !== null,
                $this->comments_count,
            ),
            'attachments_count' => $this->when(
                $this->attachments_count !== null,
                $this->attachments_count,
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

- [ ] **Step 5: Create CommentResource** (`app/Http/Resources/CommentResource.php`)

```bash
php artisan make:resource CommentResource
```

```php
<?php

declare(strict_types=1);

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

- [ ] **Step 6: Update API routes** (`routes/api.php`)

```php
<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\AuthController;
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

- [ ] **Step 7: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add Task CRUD API with pagination, filtering, and sorting"
```

---

## Task 5: Comment System API

**Files to create:**

- [ ] **Step 1: Create CommentService** (`app/Services/CommentService.php`)

```bash
php artisan make:class Services/CommentService
```

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CommentAdded;
use App\Models\TaskComment;

class CommentService
{
    public function getCommentsForTask(int $taskId): \Illuminate\Database\Eloquent\Collection
    {
        return TaskComment::with('user:id,name,email')
            ->where('task_id', $taskId)
            ->latest()
            ->get();
    }

    public function createComment(int $taskId, array $data): TaskComment
    {
        $data['task_id'] = $taskId;
        $data['user_id'] = auth()->id();

        $comment = TaskComment::create($data);
        $comment->load('user:id,name,email');

        CommentAdded::dispatch($comment);

        return $comment;
    }

    public function updateComment(TaskComment $comment, array $data): TaskComment
    {
        $comment->update($data);

        return $comment;
    }

    public function deleteComment(TaskComment $comment): bool
    {
        return $comment->delete();
    }

    public function getCommentForUser(int $commentId): ?TaskComment
    {
        return TaskComment::where('user_id', auth()->id())->find($commentId);
    }
}
```

- [ ] **Step 2: Create CommentController** (`app/Http/Controllers/Api/CommentController.php`)

```bash
php artisan make:controller Api/CommentController
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function index(int $taskId): JsonResponse
    {
        Task::findOrFail($taskId);

        $comments = $this->commentService->getCommentsForTask($taskId);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
        ]);
    }

    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        Task::findOrFail($taskId);

        $comment = $this->commentService->createComment($taskId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => new CommentResource($comment),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse
    {
        $comment = $this->commentService->getCommentForUser($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or unauthorized',
            ], Response::HTTP_NOT_FOUND);
        }

        $comment = $this->commentService->updateComment($comment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    public function destroy(int $commentId): JsonResponse
    {
        $comment = $this->commentService->getCommentForUser($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or unauthorized',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->commentService->deleteComment($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }
}
```

- [ ] **Step 3: Create Comment Requests**

```bash
php artisan make:request StoreCommentRequest
php artisan make:request UpdateCommentRequest
```

`app/Http/Requests/StoreCommentRequest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

`app/Http/Requests/UpdateCommentRequest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

- [ ] **Step 4: Update API routes** (`routes/api.php`)

```php
<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\AuthController;
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
    Route::apiResource('tasks', TaskController::class)->parameters(['tasks' => 'task']);

    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
});
```

- [ ] **Step 5: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add Comment CRUD API for tasks"
```

---

## Task 6: File Upload System

**Files to create:**

- [ ] **Step 1: Install intervention/image package**

```bash
composer require intervention/image
```

- [ ] **Step 2: Create FileService** (`app/Services/FileService.php`)

```bash
php artisan make:class Services/FileService
```

```php
<?php

declare(strict_types=1);

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
        private readonly ImageManager $imageManager,
    ) {}

    /** @return array<string, mixed> */
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
            $this->generateThumbnail($fullPath);
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
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
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

    private function generateThumbnail(string $originalPath): void
    {
        $originalFullPath = Storage::disk('public')->path($originalPath);
        $pathInfo = pathinfo($originalPath);
        $thumbnailDir = Storage::disk('public')->path($pathInfo['dirname']) . '/thumbnails';

        if (! is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailFullPath = $thumbnailDir . '/' . $pathInfo['basename'];

        $image = $this->imageManager->read($originalFullPath);
        $image->cover(200, 200)->save($thumbnailFullPath, 80);
    }
}
```

- [ ] **Step 3: Create AttachmentController** (`app/Http/Controllers/Api/AttachmentController.php`)

```bash
php artisan make:controller Api/AttachmentController
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Task;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
    ) {}

    public function index(int $taskId): JsonResponse
    {
        Task::findOrFail($taskId);

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
        Task::findOrFail($taskId);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        try {
            $file = $request->file('file');
            $uploadData = $this->fileService->upload($file, $taskId, auth()->id());

            $attachment = Attachment::create($uploadData);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $attachment,
            ], HttpResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function download(int $id): Response|JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (! Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        $fullPath = $this->fileService->getFullPath($attachment->file_path);

        return response()->download($fullPath, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function thumbnail(int $id): Response|JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (! $attachment->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'Not an image',
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        $thumbnailPath = $this->fileService->getThumbnailPath($attachment->file_path);

        if (! $thumbnailPath) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail not found',
            ], HttpResponse::HTTP_NOT_FOUND);
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

- [ ] **Step 4: Update API routes** (`routes/api.php`)

```php
<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\AuthController;
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
    Route::apiResource('tasks', TaskController::class)->parameters(['tasks' => 'task']);

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

- [ ] **Step 5: Create storage link and commit**

```bash
php artisan storage:link
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add file upload system with thumbnail generation"
```

---

## Task 7: Background Jobs & Events

**Files to create:**

- [ ] **Step 1: Create queue tables**

```bash
php artisan make:migration create_jobs_table
php artisan queue:table
php artisan migrate
```

- [ ] **Step 2: Create SendTaskAssignmentEmail Job** (`app/Jobs/SendTaskAssignmentEmail.php`)

```bash
php artisan make:job SendTaskAssignmentEmail
```

```php
<?php

declare(strict_types=1);

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

    public int $tries = 3;

    /** @var array<int, int> */
    public $backoff = [1, 5, 10];

    public function __construct(
        public readonly Task $task,
        public readonly User $assignedUser,
    ) {}

    public function handle(): void
    {
        Mail::to($this->assignedUser->email)->send(
            new TaskAssignedMail($this->task, $this->assignedUser),
        );
    }

    public function failed(?\Throwable $exception): void
    {
        \Log::error('Failed to send task assignment email', [
            'task_id' => $this->task->id,
            'user_id' => $this->assignedUser->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
```

- [ ] **Step 3: Create TaskAssignedMail** (`app/Mail/TaskAssignedMail.php`)

```bash
php artisan make:mail TaskAssignedMail
```

```php
<?php

declare(strict_types=1);

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
        public readonly User $user,
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
        );
    }

    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
```

- [ ] **Step 4: Create email template** (`resources/views/emails/task-assigned.blade.php`)

```bash
mkdir -p resources/views/emails
```

```blade
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
```

- [ ] **Step 5: Create TaskUpdated Event** (`app/Events/TaskUpdated.php`)

```bash
php artisan make:event TaskUpdated
```

```php
<?php

declare(strict_types=1);

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
        public readonly string $action = 'updated',
    ) {}

    /** @return array<int, Channel> */
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

- [ ] **Step 6: Create CommentAdded Event** (`app/Events/CommentAdded.php`)

```bash
php artisan make:event CommentAdded
```

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TaskComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TaskComment $comment,
    ) {}

    /** @return array<int, Channel> */
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

- [ ] **Step 7: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add background jobs and event notifications"
```

---

## Task 8: Final Polish & README

**Files to create:**

- [ ] **Step 1: Update backend/README.md**

```markdown
# Task Management Backend

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

# Create storage link
php artisan storage:link

# Create queue table
php artisan queue:table
php artisan migrate

# Start server
php artisan serve --port=8000
```

## Queue Workers

For background job processing (email notifications):

```bash
php artisan queue:work
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login with email and password
- `POST /api/auth/logout` - Logout (requires auth)
- `GET /api/auth/me` - Get current user (requires auth)
- `POST /api/auth/refresh` - Refresh JWT token (requires auth)

### Tasks
- `GET /api/tasks` - List tasks with pagination & filters
- `POST /api/tasks` - Create new task
- `GET /api/tasks/{id}` - Get task detail
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task

### Comments
- `GET /api/tasks/{task}/comments` - List comments
- `POST /api/tasks/{task}/comments` - Add comment
- `PUT /api/comments/{id}` - Update comment
- `DELETE /api/comments/{id}` - Delete comment

### Attachments
- `GET /api/tasks/{task}/attachments` - List attachments
- `POST /api/tasks/{task}/attachments` - Upload file
- `GET /api/attachments/{id}/download` - Download file
- `GET /api/attachments/{id}/thumbnail` - Get image thumbnail
- `DELETE /api/attachments/{id}` - Delete file

## Test Credentials

| Role    | Email                | Password |
|---------|----------------------|----------|
| Admin   | admin@example.com    | password |
| Manager | manager@example.com  | password |
| User    | john@example.com     | password |

## API Usage Example

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Create task (with token)
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"New Task","description":"Description","priority":"high"}'
```

## Running Tests

```bash
php artisan test
```
```

- [ ] **Step 2: Update .env.example**

```env
APP_NAME="Task Management"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

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

- [ ] **Step 3: Run Pint and final commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: complete backend API with all features"
```

---

## Implementation Order

1. **Task 1:** Project Setup & JWT Authentication
2. **Task 2:** Database Migrations & Models
3. **Task 3:** Database Seeders
4. **Task 4:** Task CRUD API
5. **Task 5:** Comment System API
6. **Task 6:** File Upload System
7. **Task 7:** Background Jobs & Events
8. **Task 8:** Final Polish & README

---

## Spec Coverage

| Requirement | Task |
|-------------|------|
| Users table with roles | Task 2 |
| Tasks table with all fields | Task 2 |
| Task_comments table | Task 2 |
| Attachments table | Task 2 |
| Database seeder (5 users, 15 tasks, 10 comments) | Task 3 |
| JWT Authentication | Task 1 |
| Task CRUD with pagination/filtering | Task 4 |
| Comment CRUD | Task 5 |
| File upload with thumbnails | Task 6 |
| Background jobs (email) | Task 7 |
| Events for real-time | Task 7 |
| README documentation | Task 8 |

---

*Plan created: 2026-08-13*
*Project: Task Management Platform - Backend Phase*
