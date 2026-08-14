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

    /**
     * @OA\Get(
     *     path="/auth/tasks/{taskId}/comments",
     *     summary="List comments for a task",
     *     description="Get all comments for a specific task",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="taskId", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\Response(response=200, description="Comments retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(int $taskId): JsonResponse
    {
        $comments = $this->commentService->getCommentsForTask($taskId);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/tasks/{taskId}/comments",
     *     summary="Add comment to a task",
     *     description="Create a new comment for a specific task",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="taskId", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/CommentCreateRequest")
     *     ),
     *
     *     @OA\Response(response=201, description="Comment created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        $comment = $this->commentService->createComment($taskId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => new CommentResource($comment),
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/auth/comments/{commentId}",
     *     summary="Update a comment",
     *     description="Update an existing comment (only the comment owner can update)",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="commentId", in="path", required=true, @OA\Schema(type="integer"), description="Comment ID"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/CommentUpdateRequest")
     *     ),
     *
     *     @OA\Response(response=200, description="Comment updated successfully"),
     *     @OA\Response(response=404, description="Comment not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/auth/comments/{commentId}",
     *     summary="Delete a comment",
     *     description="Delete a comment (only the comment owner can delete)",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="commentId", in="path", required=true, @OA\Schema(type="integer"), description="Comment ID"),
     *
     *     @OA\Response(response=200, description="Comment deleted successfully"),
     *     @OA\Response(response=404, description="Comment not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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
