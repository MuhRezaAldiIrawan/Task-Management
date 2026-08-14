# Architecture decisions

This document records the key architecture decisions for the Task Management platform and explains why the current structure was chosen.

## 1. Backend and frontend are separated into two applications

The project is intentionally split into:

- `backend/` — Laravel application serving the REST API and business logic
- `frontend/` — Next.js application serving the dashboard and user interface

This separation allows each side to evolve independently:

- Laravel handles authentication, validation, persistence, task logic, comments, and file storage
- Next.js handles route-based UI, dashboard interactions, forms, and client-side state

This is a good fit for a task management system where the API is reused by multiple clients and the UI can evolve without tightly coupling the backend domain model.

## 2. Laravel is the primary business-logic layer

The backend is the source of truth for:

- authentication and JWT token issuance
- task and user management
- assignment rules
- comments and attachments
- file uploads and storage handling
- role-based access enforcement

The API layer is implemented with Laravel routing under `backend/routes/api.php`, while the controller and service logic lives in the Laravel app structure such as `app/Http/Controllers/Api` and `app/Services`.

Decision: keep business rules in Laravel rather than in the frontend so that validation and authorization remain consistent across clients.

## 3. Next.js is the user-facing application shell

The frontend uses Next.js App Router and TypeScript. It is designed around:

- dashboard pages
- task listing and filtering
- task create/edit modal flow
- task detail side panel or slide-over
- user assignment selection
- file upload UI

This choice supports a modern SPA-like experience while still keeping a clean server/client separation. The dashboard and task pages are organized in the `frontend/app` directory and reusable components live in `frontend/components`.

## 4. JWT-based authentication is used for API access

Protected API routes are guarded by JWT middleware. During login, the backend issues a bearer token that the frontend stores in browser local storage.

The frontend uses a single API client in `frontend/lib/api.ts` to:

- attach the token to authenticated requests
- redirect unauthorized users to `/auth/login`
- clear stale tokens when the session is invalid

Decision: the app uses a token-based pattern for secure API access while keeping the client-side app stateless between requests.

## 5. Tasks are modeled around user assignment and lifecycle status

Core task attributes include:

- title and description
- status (`pending`, `in_progress`, `completed`, `cancelled`)
- priority (`low`, `medium`, `high`, `urgent`)
- assigned user
- due date
- optional attachments

This provides a flexible task workflow suited for team collaboration and tracking. Assignment is resolved through the user list endpoint and is connected to the task lifecycle.

## 6. Comments and attachments are treated as task-related subresources

Comment and attachment functionality is associated with a specific task rather than modeled as standalone global resources.

Examples:

- `GET /api/auth/tasks/{task}/comments`
- `POST /api/auth/tasks/{task}/comments`
- `GET /api/auth/tasks/{task}/attachments`
- `POST /api/auth/tasks/{task}/attachments`

This keeps the domain boundaries clear: a comment or attachment belongs to a task, and it is fetched and managed in context of that parent task.

## 7. Storage is centered on Laravel filesystem layers

Uploaded files are stored by the Laravel backend and exposed through a public storage path. This keeps file ownership, validation, and cleanup logic centralized in the server.

Benefits:

- the API is the single source of truth for uploaded documents
- the frontend only uploads the file and renders the result
- storage policy remains inside the backend, not in client code

## 8. Notifications are designed as database notifications for assigned tasks

The project includes a notification pattern based on Laravel database notifications, especially for task assignment updates. The design is intended to support a notification queue or unread notification list that the frontend can render as a bell/dropdown.

This is the current intended model:

- task assigned to a user triggers a notification
- frontend can fetch unread notifications from the authenticated user
- a user can mark a notification as read

The current application has temporarily disabled the frontend notification fetch due to a backend notification issue, but the architectural decision remains that notifications should be user-scoped and persisted in the backend.

## 9. API responses use a consistent success/data envelope

For most endpoints, the API returns a structured payload like:

```json
{
  "success": true,
  "data": {},
  "message": "Optional message"
}
```

This makes the client-side layer easier to standardize and keeps API semantics consistent across tasks, comments, attachments, and auth routes.

## 10. Current technical direction

The current architecture is a pragmatic modern stack:

- Laravel for API, domain logic, persistence, authorization, and file handling
- Next.js for dashboard UX and client-side interactions
- JWT for stateless authentication
- MySQL-compatible Laravel database layer for task and user records
- local development via separate backend and frontend processes

This gives the project a clean separation of concerns while staying lightweight enough to implement and maintain in a single app team.

---

## Summary

The project follows a classic API-first architecture: the backend owns business logic and data integrity, while the frontend owns user experience and interaction flows. This decision keeps the app maintainable, testable, and easier to extend as the product grows.
