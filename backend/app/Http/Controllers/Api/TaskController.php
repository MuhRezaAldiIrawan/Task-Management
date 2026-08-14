<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    /**
     * @OA\Get(
     *     path="/auth/tasks",
     *     summary="List all tasks",
     *     description="Get paginated list of tasks with optional filtering, sorting, and search",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1), description="Page number"),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, minimum=1, maximum=100), description="Items per page"),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending", "in_progress", "completed", "cancelled"}), description="Filter by status"),
     *     @OA\Parameter(name="priority", in="query", @OA\Schema(type="string", enum={"low", "medium", "high", "urgent"}), description="Filter by priority"),
     *     @OA\Parameter(name="assigned_user_id", in="query", @OA\Schema(type="integer"), description="Filter by assigned user ID"),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", default="created_at", enum={"id", "title", "status", "priority", "due_date", "created_at", "updated_at"}), description="Sort field"),
     *     @OA\Parameter(name="sort_order", in="query", @OA\Schema(type="string", default="desc", enum={"asc", "desc"}), description="Sort direction"),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search in title and description"),
     *
     *     @OA\Response(response=200, description="Tasks retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->getTasks($request->all());

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/tasks",
     *     summary="Create a new task",
     *     description="Create a new task with the provided details. If assigned_user_id is provided, an email notification will be sent.",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaskCreateRequest")
     *     ),
     *
     *     @OA\Response(response=201, description="Task created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => new TaskResource($task),
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/auth/tasks/{id}",
     *     summary="Get task details",
     *     description="Get detailed information about a specific task including comments and attachments",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\Response(response=200, description="Task details retrieved successfully"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getTask($id);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/auth/tasks/{id}",
     *     summary="Update a task",
     *     description="Update an existing task with new details",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaskUpdateRequest")
     *     ),
     *
     *     @OA\Response(response=200, description="Task updated successfully"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $task = $this->taskService->updateTask($task, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/auth/tasks/{id}",
     *     summary="Delete a task",
     *     description="Delete a task and all associated comments and attachments",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Task ID"),
     *
     *     @OA\Response(response=200, description="Task deleted successfully"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $this->taskService->deleteTask($task);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }
}
