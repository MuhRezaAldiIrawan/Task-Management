'use client';

import { Task } from '@/types';
import { TaskCard } from './TaskCard';
import { TaskCardSkeleton, EmptyState } from '@/components/ui';

interface TaskGridProps {
  tasks: Task[];
  isLoading?: boolean;
  onTaskClick?: (task: Task) => void;
  selectedTaskId?: number | null;
  emptyMessage?: string;
}

export function TaskGrid({
  tasks,
  isLoading,
  onTaskClick,
  selectedTaskId,
  emptyMessage = 'No tasks found',
}: TaskGridProps) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <TaskCardSkeleton key={i} />
        ))}
      </div>
    );
  }

  if (tasks.length === 0) {
    return (
      <EmptyState
        icon="inbox"
        title={emptyMessage}
        description="Create a new task to get started"
      />
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {tasks.map((task) => (
        <TaskCard
          key={task.id}
          task={task}
          onClick={() => onTaskClick?.(task)}
          isSelected={task.id === selectedTaskId}
        />
      ))}
    </div>
  );
}
