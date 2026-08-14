<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mimeType = $this->mime_type ?? $this->file_type ?? null;

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'mime_type' => $mimeType,
            'file_type' => $mimeType,
            'file_size' => $this->file_size,
            'uploaded_by' => $this->uploaded_by,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
