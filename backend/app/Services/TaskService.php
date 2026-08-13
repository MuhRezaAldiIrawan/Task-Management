<?php

declare(strict_types=1);

namespace App\Services;

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

        return Task::create($data);
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
        $task->update($data);

        return $task->fresh(['assignedUser:id,name,email', 'creator:id,name,email']);
    }

    public function deleteTask(Task $task): bool
    {
        return $task->delete();
    }
}
