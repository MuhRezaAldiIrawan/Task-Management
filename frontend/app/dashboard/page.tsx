'use client';

import { useState, useEffect, useCallback } from 'react';
import { Task, TaskFilters, TaskStatus, TaskPriority } from '@/types';
import { api } from '@/lib/api';
import { MainLayout } from '@/components/layout';
import { ToastContainer } from '@/components/ui';
import { Button } from '@/components/ui/Button';
import { TaskGrid, TaskFilters as TaskFiltersComponent, TaskForm, TaskDetail } from '@/components/tasks';
import { Plus } from 'lucide-react';
import { Pagination } from '@/components/ui/Pagination';

export default function DashboardPage() {
  const [tasks, setTasks] = useState<Task[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [pagination, setPagination] = useState({
    currentPage: 1,
    lastPage: 1,
    perPage: 12,
    total: 0,
  });
  const [filters, setFilters] = useState<TaskFilters>({
    status: undefined,
    priority: undefined,
    search: undefined,
  });
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [isTaskDetailOpen, setIsTaskDetailOpen] = useState(false);
  const [isTaskFormOpen, setIsTaskFormOpen] = useState(false);

  const fetchTasks = useCallback(async (page = 1) => {
    setIsLoading(true);
    try {
      const params: Record<string, string | number | string[]> = {
        page,
        per_page: pagination.perPage,
      };

      if (filters.status?.length) {
        params.status = filters.status;
      }
      if (filters.priority?.length) {
        params.priority = filters.priority;
      }
      if (filters.search) {
        params.search = filters.search;
      }

      const response = await api.getTasks(params);

      setTasks(response.data);
      setPagination((prev) => ({
        ...prev,
        currentPage: response.meta.current_page,
        lastPage: response.meta.last_page,
        total: response.meta.total,
      }));
    } catch (error) {
      console.error('Failed to fetch tasks:', error);
    } finally {
      setIsLoading(false);
    }
  }, [filters, pagination.perPage]);

  useEffect(() => {
    fetchTasks(1);
  }, [filters, fetchTasks]);

  const handlePageChange = (page: number) => {
    fetchTasks(page);
  };

  const handleTaskClick = (task: Task) => {
    setSelectedTask(task);
    setIsTaskDetailOpen(true);
  };

  const handleFiltersChange = (newFilters: { status?: TaskStatus[]; priority?: TaskPriority[]; search?: string }) => {
    setFilters((prev) => ({ ...prev, ...newFilters }));
    setPagination((prev) => ({ ...prev, currentPage: 1 }));
  };

  const handleTaskCreated = (task: Task) => {
    setTasks((prev) => [task, ...prev]);
    setPagination((prev) => ({ ...prev, total: prev.total + 1 }));
  };

  const handleTaskUpdated = (updatedTask: Task) => {
    setTasks((prev) =>
      prev.map((t) => (t.id === updatedTask.id ? updatedTask : t))
    );
    if (selectedTask?.id === updatedTask.id) {
      setSelectedTask(updatedTask);
    }
  };

  const handleTaskDeleted = (taskId: number) => {
    setTasks((prev) => prev.filter((t) => t.id !== taskId));
    setPagination((prev) => ({ ...prev, total: prev.total - 1 }));
  };

  return (
    <MainLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-[var(--color-text-primary)]">
              My Tasks
            </h1>
            <p className="text-sm text-[var(--color-text-secondary)] mt-1">
              {pagination.total} {pagination.total === 1 ? 'task' : 'tasks'} total
            </p>
          </div>
          <Button onClick={() => setIsTaskFormOpen(true)}>
            <Plus className="h-4 w-4 mr-1.5" />
            New Task
          </Button>
        </div>

        {/* Filters */}
        <TaskFiltersComponent filters={filters} onFiltersChange={handleFiltersChange} />

        {/* Task Grid */}
        <TaskGrid
          tasks={tasks}
          isLoading={isLoading}
          onTaskClick={handleTaskClick}
          selectedTaskId={selectedTask?.id}
        />

        {/* Pagination */}
        <div className="flex items-center justify-between">
          <p className="text-sm text-[var(--color-text-muted)]">
            Showing {tasks.length} of {pagination.total} tasks
          </p>
          <Pagination
            currentPage={pagination.currentPage}
            lastPage={pagination.lastPage}
            onPageChange={handlePageChange}
          />
        </div>
      </div>

      {/* Task Form Modal */}
      <TaskForm
        isOpen={isTaskFormOpen}
        onClose={() => setIsTaskFormOpen(false)}
        onSuccess={handleTaskCreated}
      />

      {/* Task Detail Slide-over */}
      <TaskDetail
        taskId={selectedTask?.id || null}
        isOpen={isTaskDetailOpen}
        onClose={() => {
          setIsTaskDetailOpen(false);
          setSelectedTask(null);
        }}
        onTaskUpdated={handleTaskUpdated}
        onTaskDeleted={handleTaskDeleted}
      />

      <ToastContainer />
    </MainLayout>
  );
}
