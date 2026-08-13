<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Task Assigned</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background-color: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .task-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }
        .task-info {
            background-color: white;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .task-info p {
            margin: 8px 0;
        }
        .label {
            font-weight: 600;
            color: #6b7280;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .priority-high { color: #dc2626; }
        .priority-medium { color: #d97706; }
        .priority-low { color: #059669; }
        .description {
            background-color: white;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            white-space: pre-wrap;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Task Assigned</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name }},</p>

        <p>You have been assigned a new task:</p>

        <div class="task-title">{{ $task->title }}</div>

        <div class="task-info">
            <p>
                <span class="label">Status:</span>
                <span class="status status-{{ $task->status }}">{{ str_replace('_', ' ', $task->status) }}</span>
            </p>
            <p>
                <span class="label">Priority:</span>
                <span class="priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
            </p>
            @if($task->due_date)
            <p>
                <span class="label">Due Date:</span>
                {{ $task->due_date->format('F j, Y') }}
            </p>
            @endif
            <p>
                <span class="label">Assigned By:</span>
                {{ $task->creator->name ?? 'System' }}
            </p>
        </div>

        @if($task->description)
        <div class="description">
            <span class="label">Description:</span>
            <br><br>
            {{ $task->description }}
        </div>
        @endif

        <p>Please log in to the Task Management Platform to view more details.</p>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
