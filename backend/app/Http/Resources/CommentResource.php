<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $createdAt = $this->created_at;
        $updatedAt = $this->updated_at;

        return [
            'id' => $this->id,
            'content' => $this->comment,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt->toIso8601String() : $createdAt,
            'updated_at' => $updatedAt instanceof \DateTimeInterface ? $updatedAt->toIso8601String() : $updatedAt,
        ];
    }
}
