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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
    ) {}

    /**
     * @OA\Get(
     *     path="/auth/tasks/{taskId}/attachments",
     *     summary="List attachments for a task",
     *     description="Get all file attachments for a specific task",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="taskId", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\Response(response=200, description="Attachments retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/auth/tasks/{taskId}/attachments",
     *     summary="Upload attachment to a task",
     *     description="Upload a file attachment to a specific task. Supported formats: images (JPEG, PNG, GIF, WebP), documents (PDF, DOC, DOCX, XLS, XLSX), text files, CSV, video (MP4, WebM), and ZIP archives. Maximum file size: 50MB.",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="taskId", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(mediaType="multipart/form-data",
     *
     *             @OA\Schema(required={"file"},
     *
     *                 @OA\Property(property="file", type="string", format="binary", description="File to upload (max 50MB)")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="File uploaded successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/auth/attachments/{id}/download",
     *     summary="Download attachment",
     *     description="Download a file attachment by its ID",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Attachment ID"),
     *
     *     @OA\Response(response=200, description="File download started"),
     *     @OA\Response(response=404, description="File not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function download(int $id): Response|JsonResponse|BinaryFileResponse
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

    /**
     * @OA\Get(
     *     path="/auth/attachments/{id}/thumbnail",
     *     summary="Get attachment thumbnail",
     *     description="Get thumbnail image for image attachments (JPEG, PNG, GIF, WebP)",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Attachment ID"),
     *
     *     @OA\Response(response=200, description="Thumbnail image"),
     *     @OA\Response(response=404, description="Thumbnail not found or attachment is not an image"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function thumbnail(int $id): Response|JsonResponse|BinaryFileResponse
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

    /**
     * @OA\Delete(
     *     path="/auth/attachments/{id}",
     *     summary="Delete attachment",
     *     description="Delete a file attachment and its thumbnail (if exists)",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Attachment ID"),
     *
     *     @OA\Response(response=200, description="File deleted successfully"),
     *     @OA\Response(response=404, description="Attachment not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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
