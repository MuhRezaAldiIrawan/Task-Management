<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaskComment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CommentService
{
    private const COMMENT_LIST_CACHE_TTL = 300; // 5 minutes

    public function getCommentsForTask(int $taskId): Collection
    {
        $cacheKey = "task:{$taskId}:comments";

        return Cache::remember(
            $cacheKey,
            self::COMMENT_LIST_CACHE_TTL,
            fn () => TaskComment::with('user:id,name,email')
                ->where('task_id', $taskId)
                ->latest()
                ->get()
        );
    }

    public function createComment(int $taskId, array $data): TaskComment
    {
        $data['task_id'] = $taskId;
        $data['user_id'] = auth()->id();
        $comment = TaskComment::create($data);
        $comment->load('user:id,name,email');

        // Invalidate task comment cache
        Cache::forget("task:{$taskId}:comments");

        return $comment;
    }

    public function updateComment(TaskComment $comment, array $data): TaskComment
    {
        $comment->update($data);

        // Invalidate task comment cache
        Cache::forget("task:{$comment->task_id}:comments");

        return $comment;
    }

    public function deleteComment(TaskComment $comment): bool
    {
        $taskId = $comment->task_id;
        $result = $comment->delete();

        // Invalidate task comment cache
        Cache::forget("task:{$taskId}:comments");

        return $result;
    }

    public function getCommentForUser(int $commentId): ?TaskComment
    {
        return TaskComment::where('user_id', auth()->id())->find($commentId);
    }
}
