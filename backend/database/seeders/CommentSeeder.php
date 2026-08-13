<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaskComment;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $comments = [
            ['task_id' => 1, 'user_id' => 2, 'comment' => 'I have started researching the best hosting providers. Will update by tomorrow.'],
            ['task_id' => 3, 'user_id' => 2, 'comment' => 'Initial mockups are ready for review.'],
            ['task_id' => 3, 'user_id' => 4, 'comment' => 'I have updated the color scheme based on feedback.'],
            ['task_id' => 4, 'user_id' => 2, 'comment' => 'Documented the authentication endpoints.'],
            ['task_id' => 5, 'user_id' => 5, 'comment' => 'Found 3 slow queries that need optimization.'],
            ['task_id' => 9, 'user_id' => 3, 'comment' => 'Working on test coverage for the login flow.'],
            ['task_id' => 13, 'user_id' => 4, 'comment' => 'Fixed the navigation menu on mobile view.'],
            ['task_id' => 13, 'user_id' => 2, 'comment' => 'Please also check the footer on tablet sizes.'],
            ['task_id' => 8, 'user_id' => 1, 'comment' => 'This is a high priority task. Please allocate more time.'],
            ['task_id' => 14, 'user_id' => 1, 'comment' => 'Looking into automated backup solutions with AWS.'],
        ];

        foreach ($comments as $comment) {
            TaskComment::create($comment);
        }
    }
}
