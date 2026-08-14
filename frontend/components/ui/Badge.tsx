'use client';

import { cn } from '@/lib/utils';
import { TaskStatus, TaskPriority, STATUS_CONFIG, PRIORITY_CONFIG } from '@/types';

interface BadgeProps {
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
  size?: 'sm' | 'md';
  children: React.ReactNode;
  className?: string;
}

export function Badge({ variant = 'default', size = 'sm', children, className }: BadgeProps) {
  const variants = {
    default: 'bg-slate-100 text-slate-700',
    success: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-red-100 text-red-700',
    info: 'bg-blue-100 text-blue-700',
  };

  const sizes = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
  };

  return (
    <span
      className={cn(
        'inline-flex items-center font-medium rounded-full uppercase tracking-wide',
        variants[variant],
        sizes[size],
        className
      )}
    >
      {children}
    </span>
  );
}

// Status Badge Component
interface StatusBadgeProps {
  status: TaskStatus;
  size?: 'sm' | 'md';
}

export function StatusBadge({ status, size = 'sm' }: StatusBadgeProps) {
  const config = STATUS_CONFIG[status];

  return (
    <span
      className={cn(
        'inline-flex items-center font-medium rounded-full uppercase tracking-wide',
        config.bgColor,
        config.color,
        size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm'
      )}
    >
      {config.label}
    </span>
  );
}

// Priority Badge Component
interface PriorityBadgeProps {
  priority: TaskPriority;
  showLabel?: boolean;
}

export function PriorityBadge({ priority, showLabel = true }: PriorityBadgeProps) {
  const config = PRIORITY_CONFIG[priority];
  const dotColors = {
    low: 'bg-slate-400',
    medium: 'bg-amber-400',
    high: 'bg-orange-500',
    urgent: 'bg-red-500',
  };

  const dotCount = {
    low: 1,
    medium: 1,
    high: 2,
    urgent: 3,
  };

  return (
    <div className="flex items-center gap-1">
      <div className="flex gap-0.5">
        {Array.from({ length: 3 }).map((_, i) => (
          <span
            key={i}
            className={cn(
              'h-2 w-1.5 rounded-sm',
              i < dotCount[priority] ? dotColors[priority] : 'bg-slate-200'
            )}
          />
        ))}
      </div>
      {showLabel && (
        <span className={cn('text-xs font-medium', config.color)}>
          {config.label}
        </span>
      )}
    </div>
  );
}
