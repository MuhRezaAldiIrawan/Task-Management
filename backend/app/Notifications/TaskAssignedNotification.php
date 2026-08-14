<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly ?User $assignedBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigned_by' => $this->assignedBy?->name ?? 'System',
            'message' => $this->assignedBy
                ? sprintf('%s assigned you to "%s".', $this->assignedBy->name, $this->task->title)
                : sprintf('You were assigned to "%s".', $this->task->title),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'message' => $this->assignedBy
                ? sprintf('%s assigned you to "%s".', $this->assignedBy->name, $this->task->title)
                : sprintf('You were assigned to "%s".', $this->task->title),
        ]);
    }
}
