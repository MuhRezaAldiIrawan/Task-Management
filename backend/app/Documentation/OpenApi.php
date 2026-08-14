<?php

declare(strict_types=1);

namespace App\Documentation;

use OpenApi\Attributes as OA;

/**
 * OpenAPI Documentation
 *
 * This class contains all API documentation including paths and schemas.
 */
#[OA\OpenApi(
    info: new OA\Info(
        title: 'Task Management API',
        version: '1.0.0',
        description: <<<'DESC'
REST API for Task Management Platform with JWT Authentication.

This API provides endpoints for:
- **Authentication**: User login, logout, token refresh
- **Tasks**: CRUD operations for task management
- **Comments**: Task discussion and comments
- **Attachments**: File uploads and management

## Authentication
All protected endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer {token}
```

Login to receive a token using the `/auth/login` endpoint.
DESC,
        contact: new OA\Contact(email: 'admin@example.com'),
        license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT')
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8000/api', description: 'Local Development Server'),
    ],
    security: [
        new OA\SecurityScheme(
            securityScheme: 'bearerAuth',
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT',
            description: 'Enter JWT token in format: Bearer {token}'
        ),
    ],
    tags: [
        new OA\Tag(name: 'Authentication', description: 'Authentication endpoints for user login, logout, and token management'),
        new OA\Tag(name: 'Tasks', description: 'Task management endpoints for CRUD operations'),
        new OA\Tag(name: 'Comments', description: 'Comment management endpoints for task discussions'),
        new OA\Tag(name: 'Attachments', description: 'File attachment management endpoints'),
    ]
)]
class OpenApi {}

/**
 * Authentication Paths
 */
trait AuthenticationPaths
{
    #[OA\Post(
        path: '/auth/login',
        summary: 'User login',
        description: 'Authenticate user and receive JWT token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'access_token', type: 'string'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                                new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/auth/logout',
        summary: 'User logout',
        description: 'Invalidate current JWT token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully logged out',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function logout(): void {}

    #[OA\Get(
        path: '/auth/me',
        summary: 'Get current user',
        description: 'Get authenticated user details',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User details retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                                new OA\Property(property: 'role', type: 'string', enum: ['admin', 'manager', 'user'], example: 'admin'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function me(): void {}

    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Refresh JWT token',
        description: 'Get a new JWT token using current token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'access_token', type: 'string'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                                new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function refresh(): void {}
}

/**
 * Task Paths
 */
trait TaskPaths
{
    #[OA\Get(
        path: '/auth/tasks',
        summary: 'List all tasks',
        description: 'Get paginated list of tasks with optional filtering, sorting, and search',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100), description: 'Items per page'),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled']), description: 'Filter by status'),
            new OA\Parameter(name: 'priority', in: 'query', schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high', 'urgent']), description: 'Filter by priority'),
            new OA\Parameter(name: 'assigned_user_id', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filter by assigned user'),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'created_at'), description: 'Sort field'),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc']), description: 'Sort direction'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search in title and description'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tasks retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/auth/tasks',
        summary: 'Create a new task',
        description: 'Create a new task with the provided details',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'status', 'priority'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'New task title'),
                    new OA\Property(property: 'description', type: 'string', example: 'Task description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'], example: 'pending'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'medium'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-08-20'),
                    new OA\Property(property: 'assigned_user_id', type: 'integer', nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Task created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/auth/tasks/{id}',
        summary: 'Get task details',
        description: 'Get detailed information about a specific task including comments and attachments',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task details retrieved successfully'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/auth/tasks/{id}',
        summary: 'Update a task',
        description: 'Update an existing task with new details',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Updated task title'),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'], example: 'in_progress'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-08-25'),
                    new OA\Property(property: 'assigned_user_id', type: 'integer', nullable: true, example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task updated successfully'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/auth/tasks/{id}',
        summary: 'Delete a task',
        description: 'Delete a task and all associated comments and attachments',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task deleted successfully'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(): void {}
}

/**
 * Comment Paths
 */
trait CommentPaths
{
    #[OA\Get(
        path: '/auth/tasks/{taskId}/comments',
        summary: 'List comments for a task',
        description: 'Get all comments for a specific task',
        tags: ['Comments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comments retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/auth/tasks/{taskId}/comments',
        summary: 'Add comment to a task',
        description: 'Create a new comment for a specific task',
        tags: ['Comments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'This is my comment'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Comment created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(): void {}

    #[OA\Put(
        path: '/auth/comments/{commentId}',
        summary: 'Update a comment',
        description: 'Update an existing comment (only the comment owner can update)',
        tags: ['Comments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Comment ID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'Updated comment content'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Comment updated successfully'),
            new OA\Response(response: 404, description: 'Comment not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/auth/comments/{commentId}',
        summary: 'Delete a comment',
        description: 'Delete a comment (only the comment owner can delete)',
        tags: ['Comments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Comment ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comment deleted successfully'),
            new OA\Response(response: 404, description: 'Comment not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(): void {}
}

/**
 * Attachment Paths
 */
trait AttachmentPaths
{
    #[OA\Get(
        path: '/auth/tasks/{taskId}/attachments',
        summary: 'List attachments for a task',
        description: 'Get all file attachments for a specific task',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Attachments retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/auth/tasks/{taskId}/attachments',
        summary: 'Upload attachment to a task',
        description: 'Upload a file attachment. Supported formats: images (JPEG, PNG, GIF, WebP), documents (PDF, DOC, DOCX, XLS, XLSX), text files, CSV, video (MP4, WebM), ZIP. Max size: 50MB.',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data',
                schema: new OA\Schema(required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File to upload (max 50MB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'File uploaded successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/auth/attachments/{id}/download',
        summary: 'Download attachment',
        description: 'Download a file attachment by its ID',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Attachment ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download started'),
            new OA\Response(response: 404, description: 'File not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function download(): void {}

    #[OA\Get(
        path: '/auth/attachments/{id}/thumbnail',
        summary: 'Get attachment thumbnail',
        description: 'Get thumbnail image for image attachments (JPEG, PNG, GIF, WebP)',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Attachment ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thumbnail image'),
            new OA\Response(response: 404, description: 'Thumbnail not found or not an image'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function thumbnail(): void {}

    #[OA\Delete(
        path: '/auth/attachments/{id}',
        summary: 'Delete attachment',
        description: 'Delete a file attachment and its thumbnail (if exists)',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Attachment ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File deleted successfully'),
            new OA\Response(response: 404, description: 'Attachment not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(): void {}
}
