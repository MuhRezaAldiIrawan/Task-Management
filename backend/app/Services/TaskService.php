<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TaskUpdated;
use App\Jobs\SendTaskAssignmentEmail;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TaskService
{
    private const TASK_LIST_CACHE_TTL = 120; // 2 minutes

    private const TASK_DETAIL_CACHE_TTL = 300; // 5 minutes

    public function getTasks(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);

        // Create cache key based on filters
        $cacheKey = $this->buildTasksCacheKey($filters);

        return Cache::remember(
            $cacheKey,
            self::TASK_LIST_CACHE_TTL,
            fn () => Task::with(['assignedUser:id,name,email', 'creator:id,name,email'])
                ->filter($filters)
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage)
        );
    }

    public function createTask(array $data): Task
    {
        $data['created_by'] = auth()->id();

        $task = Task::create($data);

        if (! empty($task->assigned_user_id)) {
            $task->load('assignedUser:id,name,email');
            SendTaskAssignmentEmail::dispatch($task, $task->assignedUser);
        }

        TaskUpdated::dispatch($task, 'created');

        // Invalidate task list cache
        $this->invalidateTasksListCache();

        return $task;
    }

    public function getTask(int $id): Task
    {
        $cacheKey = "task:{$id}";

        return Cache::remember(
            $cacheKey,
            self::TASK_DETAIL_CACHE_TTL,
            fn () => Task::with([
                'assignedUser:id,name,email',
                'creator:id,name,email',
                'comments.user:id,name,email',
                'attachments',
            ])->findOrFail($id)
        );
    }

    public function updateTask(Task $task, array $data): Task
    {
        $wasAssigned = $task->assigned_user_id;
        $task->update($data);
        $task->refresh();

        if (
            ! empty($task->assigned_user_id) &&
            ($wasAssigned !== $task->assigned_user_id || ! $wasAssigned)
        ) {
            SendTaskAssignmentEmail::dispatch($task, $task->assignedUser);
        }

        TaskUpdated::dispatch($task, 'updated');

        // Invalidate caches
        $this->invalidateTaskCache($task->id);
        $this->invalidateTasksListCache();

        return $task->fresh(['assignedUser:id,name,email', 'creator:id,name,email']);
    }

    public function deleteTask(Task $task): bool
    {
        $taskId = $task->id;
        $result = $task->delete();

        // Invalidate caches
        $this->invalidateTaskCache($taskId);
        $this->invalidateTasksListCache();

        return $result;
    }

    /**
     * Build cache key based on filters
     */
    private function buildTasksCacheKey(array $filters): string
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);

        $filterHash = md5(json_encode($filters));

        return "tasks:list:{$filterHash}:page{$page}:per{$perPage}:sort{$sortBy}:{$sortOrder}";
    }

    /**
     * Invalidate a specific task cache
     */
    private function invalidateTaskCache(int $taskId): void
    {
        Cache::forget("task:{$taskId}");
    }

    /**
     * Invalidate all task list caches
     * Uses cache tags if available (Redis)
     */
    private function invalidateTasksListCache(): void
    {
        // For Redis, we can use tags to invalidate all task list caches
        if ($this->supportsCacheTags()) {
            Cache::tags(['task-list'])->flush();
        } else {
            // Fallback: Cache will expire naturally via TTL
            // For production, consider using Cache::flexible() pattern
        }
    }

    /**
     * Check if cache driver supports tags
     */
    private function supportsCacheTags(): bool
    {
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'dynamodb'], true);
    }
}
