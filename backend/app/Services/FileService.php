<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

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

    private const THUMBNAIL_SIZE = 200;

    public function __construct(
        private readonly ImageManager $imageManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function upload(UploadedFile $file, int $taskId, int $userId): array
    {
        $this->validateFile($file);

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $path = "attachments/{$taskId}/{$filename}";

        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        $fullPath = Storage::disk('public')->path($path);

        if ($this->isImageFile($file)) {
            $this->generateThumbnail($fullPath);
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    public function delete(string $filePath): bool
    {
        $fullPath = $this->getFullPath($filePath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $thumbnailPath = $this->getThumbnailPath($filePath);
        if ($thumbnailPath !== null && file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        return true;
    }

    public function getFullPath(string $relativePath): string
    {
        return Storage::disk('public')->path($relativePath);
    }

    public function getThumbnailPath(string $filePath): ?string
    {
        $directory = dirname($filePath);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = 'jpg';

        $thumbnailPath = "{$directory}/thumb_{$filename}.{$extension}";

        return Storage::disk('public')->exists($thumbnailPath) ? $thumbnailPath : null;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('File type not allowed.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds 50MB limit.');
        }
    }

    private function isImageFile(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    private function generateThumbnail(string $originalPath): void
    {
        $directory = dirname($originalPath);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $thumbnailFilename = "thumb_{$filename}.jpg";
        $thumbnailPath = "{$directory}/{$thumbnailFilename}";

        $image = $this->imageManager->read($originalPath);
        $image->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);

        Storage::disk('public')->put(
            $thumbnailPath,
            $image->toJpeg(80)
        );
    }
}
