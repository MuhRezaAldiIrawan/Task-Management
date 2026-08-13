<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaskComment;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    public function getCommentsForTask(int $taskId): Collection
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
