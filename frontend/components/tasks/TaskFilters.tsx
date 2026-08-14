'use client';

import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/Button';
import { TaskStatus, TaskPriority, STATUS_CONFIG, PRIORITY_CONFIG } from '@/types';
import { Filter, X, Search } from 'lucide-react';

interface TaskFiltersProps {
  filters: {
    status?: TaskStatus[];
    priority?: TaskPriority[];
    search?: string;
  };
  onFiltersChange: (filters: TaskFiltersProps['filters']) => void;
}

export function TaskFilters({ filters, onFiltersChange }: TaskFiltersProps) {
  const [isExpanded, setIsExpanded] = useState(false);

  const toggleStatus = (status: TaskStatus) => {
    const current = filters.status || [];
    const updated = current.includes(status)
      ? current.filter((s) => s !== status)
      : [...current, status];
    onFiltersChange({ ...filters, status: updated.length ? updated : undefined });
  };

  const togglePriority = (priority: TaskPriority) => {
    const current = filters.priority || [];
    const updated = current.includes(priority)
      ? current.filter((p) => p !== priority)
      : [...current, priority];
    onFiltersChange({ ...filters, priority: updated.length ? updated : undefined });
  };

  const clearFilters = () => {
    onFiltersChange({});
  };

  const hasActiveFilters = (filters.status?.length || 0) > 0 || (filters.priority?.length || 0) > 0;

  return (
    <div className="space-y-3">
      {/* Quick Filters */}
      <div className="flex items-center gap-2 flex-wrap">
        <div className="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
          <Filter className="h-4 w-4" />
          <span>Filter:</span>
        </div>

        {/* Status Pills */}
        <div className="flex gap-2 flex-wrap">
          {Object.entries(STATUS_CONFIG).map(([key, config]) => {
            const isActive = filters.status?.includes(key as TaskStatus);
            return (
              <button
                key={key}
                onClick={() => toggleStatus(key as TaskStatus)}
                className={cn(
                  'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                  isActive
                    ? `${config.bgColor} ${config.color}`
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                )}
              >
                {config.label}
              </button>
            );
          })}
        </div>

        <button
          onClick={() => setIsExpanded(!isExpanded)}
          className="text-xs text-[var(--color-accent)] hover:underline"
        >
          {isExpanded ? 'Less' : 'More'}
        </button>
      </div>

      {/* Expanded Filters */}
      {isExpanded && (
        <div className="p-4 bg-slate-50 rounded-lg border border-slate-200 space-y-4 animate-fade-in">
          {/* Priority Filter */}
          <div>
            <label className="text-sm font-medium text-[var(--color-text-primary)] mb-2 block">
              Priority
            </label>
            <div className="flex flex-wrap gap-2">
              {Object.entries(PRIORITY_CONFIG).map(([key, config]) => {
                const isActive = filters.priority?.includes(key as TaskPriority);
                return (
                  <button
                    key={key}
                    onClick={() => togglePriority(key as TaskPriority)}
                    className={cn(
                      'px-3 py-1.5 text-xs font-medium rounded-full transition-colors flex items-center gap-1.5',
                      isActive
                        ? `${config.bgColor} ${config.color}`
                        : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300'
                    )}
                  >
                    <span
                      className={cn(
                        'w-2 h-2 rounded-full',
                        key === 'low' && 'bg-slate-400',
                        key === 'medium' && 'bg-amber-400',
                        key === 'high' && 'bg-orange-500',
                        key === 'urgent' && 'bg-red-500'
                      )}
                    />
                    {config.label}
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* Clear Filters */}
      {hasActiveFilters && (
        <div className="flex items-center gap-2">
          <span className="text-xs text-[var(--color-text-muted)]">
            {filters.status?.length || 0} status, {filters.priority?.length || 0} priority
          </span>
          <button
            onClick={clearFilters}
            className="flex items-center gap-1 text-xs text-red-500 hover:underline"
          >
            <X className="h-3 w-3" />
            Clear all
          </button>
        </div>
      )}
    </div>
  );
}
