<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
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

    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * @return array<string, mixed>
     */
    public function upload(UploadedFile $file, int $taskId, int $userId): array
    {
        $this->validateFile($file);

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $relativePath = "attachments/{$taskId}/{$filename}";

        // Store the file using Storage facade
        $fileContent = file_get_contents($file->getRealPath());
        Storage::disk('public')->put($relativePath, $fileContent);

        if ($this->isImageFile($file)) {
            $this->generateThumbnail($relativePath);
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    public function delete(string $filePath): bool
    {
        Storage::disk('public')->delete($filePath);

        // Delete thumbnail if exists
        $thumbnailPath = $this->getThumbnailPath($filePath);
        if ($thumbnailPath) {
            Storage::disk('public')->delete($thumbnailPath);
        }

        return true;
    }

    public function getThumbnailPath(string $filePath): ?string
    {
        $directory = pathinfo($filePath, PATHINFO_DIRNAME);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $thumbnailPath = "{$directory}/thumb_{$filename}.jpg";

        return Storage::disk('public')->exists($thumbnailPath) ? $thumbnailPath : null;
    }

    public function getFullPath(string $relativePath): string
    {
        return Storage::disk('public')->path($relativePath);
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

    private function generateThumbnail(string $relativePath): void
    {
        $directory = pathinfo($relativePath, PATHINFO_DIRNAME);
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        $thumbnailPath = "{$directory}/thumb_{$filename}.jpg";

        // Read original image content
        $originalContent = Storage::disk('public')->get($relativePath);
        if (! $originalContent) {
            return;
        }

        // Create thumbnail using Intervention Image
        $image = $this->imageManager->decode($originalContent);
        $image->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);

        // Save thumbnail
        Storage::disk('public')->put($thumbnailPath, $image->encode()->toString());
    }
}
