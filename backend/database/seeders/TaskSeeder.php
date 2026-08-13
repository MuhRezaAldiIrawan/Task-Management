<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            // High priority, pending
            ['title' => 'Setup production server', 'description' => 'Configure and deploy to production server', 'status' => 'pending', 'priority' => 'high', 'assigned_user_id' => 3, 'created_by' => 2, 'due_date' => now()->addDays(7)],
            ['title' => 'Security audit', 'description' => 'Perform comprehensive security review', 'status' => 'pending', 'priority' => 'high', 'assigned_user_id' => 1, 'created_by' => 2, 'due_date' => now()->addDays(14)],

            // Medium priority, in_progress
            ['title' => 'User dashboard redesign', 'description' => 'Update dashboard UI with new design system', 'status' => 'in_progress', 'priority' => 'medium', 'assigned_user_id' => 4, 'created_by' => 2, 'due_date' => now()->addDays(10)],
            ['title' => 'API documentation', 'description' => 'Document all REST API endpoints', 'status' => 'in_progress', 'priority' => 'medium', 'assigned_user_id' => 3, 'created_by' => 2, 'due_date' => now()->addDays(5)],
            ['title' => 'Database optimization', 'description' => 'Optimize slow queries and add indexes', 'status' => 'in_progress', 'priority' => 'medium', 'assigned_user_id' => 5, 'created_by' => 2, 'due_date' => now()->addDays(8)],

            // Low priority, completed
            ['title' => 'Update dependencies', 'description' => 'Update composer and npm packages', 'status' => 'completed', 'priority' => 'low', 'assigned_user_id' => 5, 'created_by' => 2, 'due_date' => now()->subDays(3)],
            ['title' => 'Fix typo in README', 'description' => 'Correct spelling mistake in documentation', 'status' => 'completed', 'priority' => 'low', 'assigned_user_id' => 3, 'created_by' => 1, 'due_date' => now()->subDays(5)],

            // Mixed priorities, various statuses
            ['title' => 'Implement notification system', 'description' => 'Add email and push notifications', 'status' => 'pending', 'priority' => 'high', 'assigned_user_id' => 4, 'created_by' => 1, 'due_date' => now()->addDays(21)],
            ['title' => 'Unit tests for auth module', 'description' => 'Write comprehensive unit tests', 'status' => 'in_progress', 'priority' => 'medium', 'assigned_user_id' => 3, 'created_by' => 2, 'due_date' => now()->addDays(12)],
            ['title' => 'Setup CI/CD pipeline', 'description' => 'Configure GitHub Actions for automated deployment', 'status' => 'completed', 'priority' => 'high', 'assigned_user_id' => 1, 'created_by' => 1, 'due_date' => now()->subDays(2)],
            ['title' => 'Code review process', 'description' => 'Establish code review guidelines', 'status' => 'completed', 'priority' => 'medium', 'assigned_user_id' => 2, 'created_by' => 1, 'due_date' => now()->subDays(1)],
            ['title' => 'Performance monitoring', 'description' => 'Setup application performance monitoring', 'status' => 'pending', 'priority' => 'medium', 'assigned_user_id' => 5, 'created_by' => 2, 'due_date' => now()->addDays(15)],
            ['title' => 'Mobile responsiveness', 'description' => 'Fix layout issues on mobile devices', 'status' => 'in_progress', 'priority' => 'high', 'assigned_user_id' => 4, 'created_by' => 2, 'due_date' => now()->addDays(4)],
            ['title' => 'Backup strategy', 'description' => 'Implement automated backup solution', 'status' => 'pending', 'priority' => 'low', 'assigned_user_id' => 1, 'created_by' => 2, 'due_date' => now()->addDays(30)],
            ['title' => 'User feedback analysis', 'description' => 'Analyze and prioritize user feedback', 'status' => 'completed', 'priority' => 'medium', 'assigned_user_id' => 2, 'created_by' => 1, 'due_date' => now()->subDays(7)],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
