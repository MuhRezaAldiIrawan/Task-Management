<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function index(int $taskId): JsonResponse
    {
        $comments = $this->commentService->getCommentsForTask($taskId);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
        ]);
    }

    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        $comment = $this->commentService->createComment($taskId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => new CommentResource($comment),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse
    {
        $comment = $this->commentService->getCommentForUser($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or unauthorized',
            ], Response::HTTP_NOT_FOUND);
        }

        $comment = $this->commentService->updateComment($comment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    public function destroy(int $commentId): JsonResponse
    {
        $comment = $this->commentService->getCommentForUser($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or unauthorized',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->commentService->deleteComment($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }
}
