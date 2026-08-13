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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTaskAssignmentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [1, 5, 10];

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
        Log::error('Failed to send task assignment email', [
            'task_id' => $this->task->id,
            'user_id' => $this->assignedUser->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
