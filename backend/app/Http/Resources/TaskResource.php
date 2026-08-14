<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dueDate = $this->due_date;
        $createdAt = $this->created_at;
        $updatedAt = $this->updated_at;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $dueDate instanceof \DateTimeInterface ? $dueDate->toDateString() : $dueDate,
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt->toIso8601String() : $createdAt,
            'updated_at' => $updatedAt instanceof \DateTimeInterface ? $updatedAt->toIso8601String() : $updatedAt,
        ];
    }
}
