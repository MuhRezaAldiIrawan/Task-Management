'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Task, TaskStatus, TaskPriority, User } from '@/types';
import { Button, Input, Textarea, Select } from '@/components/ui';
import { Modal } from '@/components/ui/Modal';
import { useToast } from '@/lib/toast';
import { api } from '@/lib/api';

const taskSchema = z.object({
  title: z.string().min(1, 'Title is required').max(255, 'Title is too long'),
  description: z.string().optional(),
  status: z.enum(['pending', 'in_progress', 'completed', 'cancelled']).optional(),
  priority: z.enum(['low', 'medium', 'high', 'urgent']).optional(),
  assigned_user_id: z.number().optional().nullable(),
  due_date: z.string().optional().nullable(),
});

type TaskFormData = z.infer<typeof taskSchema>;

interface TaskFormProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: (task: Task) => void;
  task?: Task;
  users?: User[];
}

export function TaskForm({ isOpen, onClose, onSuccess, task, users = [] }: TaskFormProps) {
  const { addToast } = useToast();
  const [isLoading, setIsLoading] = useState(false);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<TaskFormData>({
    resolver: zodResolver(taskSchema),
    defaultValues: {
      title: task?.title || '',
      description: task?.description || '',
      status: task?.status || 'pending',
      priority: task?.priority || 'medium',
      assigned_user_id: task?.assigned_user_id || null,
      due_date: task?.due_date || '',
    },
  });

  const onSubmit = async (data: TaskFormData) => {
    setIsLoading(true);

    try {
      const payload = {
        title: data.title,
        description: data.description || undefined,
        status: data.status,
        priority: data.priority,
        assigned_user_id: data.assigned_user_id || undefined,
        due_date: data.due_date || undefined,
      };

      let response;
      if (task) {
        response = await api.updateTask(task.id, payload);
      } else {
        response = await api.createTask(payload);
      }

      addToast({
        type: 'success',
        title: task ? 'Task updated' : 'Task created',
        message: response.message,
      });

      reset();
      onSuccess(response.data);
      onClose();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to save task';
      addToast({
        type: 'error',
        title: 'Error',
        message,
      });
    } finally {
      setIsLoading(false);
    }
  };

  const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
  ];

  const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
  ];

  const userOptions = users.map((u) => ({
    value: u.id,
    label: u.name,
  }));

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={task ? 'Edit Task' : 'Create New Task'}
      size="lg"
    >
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {/* Title */}
        <Input
          label="Title"
          placeholder="Enter task title"
          error={errors.title?.message}
          required
          {...register('title')}
        />

        {/* Description */}
        <Textarea
          label="Description"
          placeholder="Describe the task..."
          {...register('description')}
        />

        {/* Status & Priority Row */}
        <div className="grid grid-cols-2 gap-4">
          <Select
            label="Status"
            options={statusOptions}
            {...register('status')}
          />
          <Select
            label="Priority"
            options={priorityOptions}
            {...register('priority')}
          />
        </div>

        {/* Assigned User */}
        <Select
          label="Assign To"
          options={[{ value: '', label: 'Unassigned' }, ...userOptions]}
          placeholder="Select a user"
          {...register('assigned_user_id', {
            setValueAs: (value) => (value ? parseInt(value, 10) : null),
          })}
        />

        {/* Due Date */}
        <Input
          type="date"
          label="Due Date"
          {...register('due_date')}
        />

        {/* Actions */}
        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" isLoading={isLoading}>
            {task ? 'Save Changes' : 'Create Task'}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
