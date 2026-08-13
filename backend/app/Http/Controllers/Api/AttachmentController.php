<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Task;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
    ) {}

    public function index(int $taskId): JsonResponse
    {
        $attachments = Attachment::where('task_id', $taskId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => AttachmentResource::collection($attachments),
        ]);
    }

    public function store(Request $request, int $taskId): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $task = Task::findOrFail($taskId);
        $user = $request->user();

        try {
            $file = $request->file('file');
            $fileData = $this->fileService->upload($file, $taskId, $user->id);

            $attachment = Attachment::create([
                'task_id' => $task->id,
                'file_name' => $fileData['file_name'],
                'file_path' => $fileData['file_path'],
                'file_size' => $fileData['file_size'],
                'mime_type' => $fileData['mime_type'],
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => new AttachmentResource($attachment),
            ], HttpResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function download(int $id): Response|JsonResponse
    {
        $attachment = Attachment::findOrFail($id);
        $fullPath = $this->fileService->getFullPath($attachment->file_path);

        if (! file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->download($fullPath, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function thumbnail(int $id): Response|JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (! $attachment->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail not available for non-image files',
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        $thumbnailPath = $this->fileService->getThumbnailPath($attachment->file_path);

        if ($thumbnailPath === null) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail not found',
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        $fullPath = $this->fileService->getFullPath($thumbnailPath);

        return response()->file($fullPath);
    }

    public function destroy(int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);

        try {
            $this->fileService->delete($attachment->file_path);
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'attachment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File deletion failed',
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
