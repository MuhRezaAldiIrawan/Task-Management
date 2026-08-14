'use client';

import { Task } from '@/types';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/utils';
import { Calendar } from 'lucide-react';
import { StatusBadge, PriorityBadge } from '@/components/ui/Badge';
import { Avatar } from '@/components/ui/Avatar';
import { Card } from '@/components/ui/Card';

interface TaskCardProps {
  task: Task;
  onClick?: () => void;
  isSelected?: boolean;
}

export function TaskCard({ task, onClick, isSelected }: TaskCardProps) {
  const hasDueDate = task.due_date;
  const isOverdue = hasDueDate && new Date(task.due_date!) < new Date() && task.status !== 'completed';

  return (
    <Card
      hover
      onClick={onClick}
      className={cn(
        'min-h-[160px] flex flex-col cursor-pointer',
        isSelected && 'ring-2 ring-[var(--color-accent)]'
      )}
    >
      {/* Header - Priority & Status */}
      <div className="flex items-center justify-between mb-3">
        <PriorityBadge priority={task.priority} />
        <StatusBadge status={task.status} />
      </div>

      {/* Title */}
      <h3 className="text-base font-semibold text-[var(--color-text-primary)] mb-2 line-clamp-2">
        {task.title}
      </h3>

      {/* Description Preview */}
      {task.description && (
        <p className="text-sm text-[var(--color-text-secondary)] mb-4 line-clamp-2 flex-1">
          {task.description}
        </p>
      )}

      {/* Footer - Assignee & Due Date */}
      <div className="flex items-center justify-between mt-auto pt-3 border-t border-slate-100">
        <div className="flex items-center gap-2">
          {task.assigned_user ? (
            <>
              <Avatar name={task.assigned_user.name} size="sm" />
              <span className="text-xs text-[var(--color-text-secondary)]">
                {task.assigned_user.name}
              </span>
            </>
          ) : (
            <span className="text-xs text-[var(--color-text-muted)]">Unassigned</span>
          )}
        </div>

        {hasDueDate && (
          <div
            className={cn(
              'flex items-center gap-1 text-xs',
              isOverdue ? 'text-red-500' : 'text-[var(--color-text-muted)]'
            )}
          >
            <Calendar className="h-3.5 w-3.5" />
            <span>{formatDate(task.due_date)}</span>
          </div>
        )}
      </div>
    </Card>
  );
}
